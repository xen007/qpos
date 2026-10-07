<?php
namespace App\Services;

use App\Models\{PointOfSale,Product,StockMovement,User};
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StockTransferService
{
    public function create(int $source,int $destination,array $items,string $reason,string $key,int $userId): object
    {
        return DB::transaction(function () use ($source,$destination,$items,$reason,$key,$userId) {
            $this->authorize($source,$userId,'stock_transfer_dispatch');
            if ($source===$destination || !PointOfSale::whereKey($destination)->where('is_active',true)->exists())
                $this->fail(__('Choose two different active shops.'));
            if (!$items || trim($reason)==='' || strlen($key)>64 || $key==='') $this->fail(__('Provide transfer lines, a reason and an operation key.'));
            // Product locks serialize transfer creation and all physical writers.
            $ids=array_map('intval',array_column($items,'product_id')); sort($ids);
            if (count(array_unique($ids))!==count($ids)) $this->fail(__('Supply each product once.'));
            Product::whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();
            $hash=hash('sha256',json_encode([$source,$destination,$items,$reason,$userId],JSON_THROW_ON_ERROR));
            $existing=DB::table('stock_transfers')->where('operation_key',$key)->lockForUpdate()->first();
            if ($existing) { $this->same($existing,$hash); return $existing; }
            $id=DB::table('stock_transfers')->insertGetId([
                'source_shop_id'=>$source,'destination_shop_id'=>$destination,'user_id'=>$userId,'operation_key'=>$key,'request_hash'=>$hash,
                'reason'=>$reason,'status'=>'draft','created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach ($items as $item) {
                $product=Product::findOrFail($item['product_id']);
                if ($product->allows_fractional===null) $this->fail(__('Configure the fractional quantity rule first.'));
                $qty=QuantityDecimal::toBase($item['quantity'],'1',(bool)$product->allows_fractional);
                DB::table('stock_transfer_items')->insert([
                    'stock_transfer_id'=>$id,'product_id'=>$product->id,'quantity'=>$qty,'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
            return DB::table('stock_transfers')->find($id);
        },3);
    }

    public function dispatch(int $id,int $userId): object
    {
        return DB::transaction(function () use ($id,$userId) {
            $transfer=DB::table('stock_transfers')->where('id',$id)->lockForUpdate()->firstOrFail();
            $this->authorize($transfer->source_shop_id,$userId,'stock_transfer_dispatch');
            if ($transfer->status!=='draft') {
                if ($transfer->dispatched_at) return $transfer;
                $this->fail(__('This transfer cannot be dispatched.'));
            }
            if (!PointOfSale::whereKey($transfer->destination_shop_id)->where('is_active',true)->exists()) $this->fail(__('The destination shop is inactive.'));
            $items=DB::table('stock_transfer_items')->where('stock_transfer_id',$id)->orderBy('product_id')->get();
            Product::whereIn('id',$items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get();
            $stock=app(StockService::class);
            foreach ($items as $item) {
                $out=$stock->decrease($transfer->source_shop_id,$item->product_id,$item->quantity,[
                    'type'=>'transfer_out','correlation_key'=>'transfer:'.$id.':dispatch:'.$item->id,'reason'=>$transfer->reason,'user_id'=>$userId,
                ]);
                foreach ($out as $move) {
                    if (!$move->product_batch_id) $this->fail(__('Approve a documented lot before transferring stock.'));
                    $quantity=(string)BigDecimal::of($move->quantity_delta)->abs();
                    $stock->correctBucket($transfer->source_shop_id,$item->product_id,$move->product_batch_id,'in_transit',$quantity,'transfer_out',
                        'transfer:'.$id.':transit:'.$item->id,$transfer->reason,$userId,$move->correlation_line);
                    DB::table('stock_transfer_allocations')->insert([
                        'stock_transfer_item_id'=>$item->id,'product_batch_id'=>$move->product_batch_id,'dispatch_movement_id'=>$move->id,
                        'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now(),
                    ]);
                }
            }
            DB::table('stock_transfers')->where('id',$id)->update(['status'=>'dispatched','dispatched_at'=>now('Africa/Douala'),'updated_at'=>now()]);
            return DB::table('stock_transfers')->find($id);
        },3);
    }

    /** Receive, physically return, or declare a documented loss of remaining transit. */
    public function settle(int $id,array $lines,string $kind,string $reason,string $key,int $userId): object
    {
        return DB::transaction(function () use ($id,$lines,$kind,$reason,$key,$userId) {
            $transfer=DB::table('stock_transfers')->where('id',$id)->lockForUpdate()->firstOrFail();
            if (!in_array($kind,['receive','return','loss'],true) || !$lines || trim($reason)==='' || $key==='' || strlen($key)>64)
                $this->fail(__('Provide a settlement kind, quantities, reason and operation key.'));
            $this->authorize($kind==='receive' ? $transfer->destination_shop_id : $transfer->source_shop_id,$userId,
                $kind==='receive' ? 'stock_transfer_receive' : 'stock_transfer_dispatch');
            $hash=hash('sha256',json_encode([$id,$lines,$kind,$reason,$userId],JSON_THROW_ON_ERROR));
            $existing=DB::table('stock_transfer_receipts')->where('operation_key',$key)->first();
            if ($existing) { $this->same($existing,$hash); return $existing; }
            if (!in_array($transfer->status,['dispatched','partial'],true)) $this->fail(__('Only outstanding dispatched stock can be settled.'));
            $allocations=DB::table('stock_transfer_allocations as a')->join('stock_transfer_items as i','i.id','=','a.stock_transfer_item_id')
                ->where('i.stock_transfer_id',$id)->select('a.*','i.product_id')->orderBy('i.product_id')->orderBy('a.id')->lockForUpdate()->get()->keyBy('id');
            Product::whereIn('id',$allocations->pluck('product_id')->unique())->orderBy('id')->lockForUpdate()->get();
            $receiptId=DB::table('stock_transfer_receipts')->insertGetId([
                'stock_transfer_id'=>$id,'user_id'=>$userId,'operation_key'=>$key,'request_hash'=>$hash,'kind'=>$kind,'reason'=>$reason,
                'occurred_at'=>now('Africa/Douala'),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $seen=[]; $stock=app(StockService::class);
            foreach ($lines as $index=>$line) {
                $allocation=$allocations->get((int)$line['allocation_id']);
                if (!$allocation || isset($seen[$allocation->id])) $this->fail(__('Invalid or duplicate transfer allocation.'));
                $seen[$allocation->id]=true;
                $quantity=QuantityDecimal::parse($line['quantity'],'quantity',true);
                $remainder=BigDecimal::of($allocation->quantity)->minus($allocation->received_quantity)->minus($allocation->returned_quantity)->minus($allocation->lost_quantity);
                if ($quantity->isGreaterThan($remainder)) $this->fail(__('Settlement exceeds the remaining transfer quantity.'));
                $move=$stock->correctBucket($transfer->source_shop_id,$allocation->product_id,$allocation->product_batch_id,'in_transit',(string)$quantity->negated(),
                    $kind==='loss' ? 'loss' : 'transfer_in','transfer-settle:'.$receiptId.':out',$reason,$userId,$index+1);
                if ($kind!=='loss') {
                    // Unknown or expired lots remain unavailable through the common availability rule.
                    $move=$stock->increase($kind==='receive' ? $transfer->destination_shop_id : $transfer->source_shop_id,$allocation->product_id,(string)$quantity,[
                        'type'=>'transfer_in','batch_id'=>$allocation->product_batch_id,'correlation_key'=>'transfer-settle:'.$receiptId.':in',
                        'correlation_line'=>$index+1,'reason'=>$reason,'user_id'=>$userId,
                    ]);
                }
                $field=match($kind) {'receive'=>'received_quantity','return'=>'returned_quantity',default=>'lost_quantity'};
                DB::table('stock_transfer_allocations')->where('id',$allocation->id)->update([$field=>(string)BigDecimal::of($allocation->$field)->plus($quantity)->toScale(6),'updated_at'=>now()]);
                DB::table('stock_transfer_receipt_items')->insert([
                    'stock_transfer_receipt_id'=>$receiptId,'stock_transfer_allocation_id'=>$allocation->id,'quantity'=>(string)$quantity,
                    'movement_id'=>$move->id,'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
            $remaining=BigDecimal::zero(); $cancelled=false;
            foreach (DB::table('stock_transfer_allocations')->whereIn('id',$allocations->keys())->get() as $row) {
                $remaining=$remaining->plus(BigDecimal::of($row->quantity)->minus($row->received_quantity)->minus($row->returned_quantity)->minus($row->lost_quantity));
                if (BigDecimal::of($row->returned_quantity)->plus($row->lost_quantity)->isPositive()) $cancelled=true;
            }
            DB::table('stock_transfers')->where('id',$id)->update([
                'status'=>$remaining->isZero() ? ($cancelled ? 'closed' : 'received') : 'partial',
                'closed_at'=>$remaining->isZero() ? now('Africa/Douala') : null,'updated_at'=>now(),
            ]);
            return DB::table('stock_transfer_receipts')->find($receiptId);
        },3);
    }

    public function cancelDraft(int $id,string $reason,int $userId): void
    {
        DB::transaction(function () use ($id,$reason,$userId) {
            $transfer=DB::table('stock_transfers')->where('id',$id)->lockForUpdate()->firstOrFail();
            $this->authorize($transfer->source_shop_id,$userId,'stock_transfer_dispatch');
            $existing=DB::table('stock_transfer_receipts')->where('stock_transfer_id',$id)->where('kind','cancel_draft')->first();
            if ($transfer->status==='cancelled' && $existing?->reason===$reason) return;
            if ($transfer->status!=='draft' || trim($reason)==='') $this->fail(__('After dispatch, record a physical return or a documented loss.'));
            DB::table('stock_transfer_receipts')->insert([
                'stock_transfer_id'=>$id,'user_id'=>$userId,'operation_key'=>'transfer-draft-cancel:'.$id,
                'request_hash'=>hash('sha256',json_encode([$id,$reason,$userId],JSON_THROW_ON_ERROR)),
                'kind'=>'cancel_draft','reason'=>$reason,'occurred_at'=>now('Africa/Douala'),'created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('stock_transfers')->where('id',$id)->update(['status'=>'cancelled','closed_at'=>now('Africa/Douala'),'updated_at'=>now()]);
        },3);
    }

    private function authorize(int $shopId,int $userId,string $permission): void
    {
        $user=User::findOrFail($userId);
        abort_unless($user->can($permission) && PointOfSale::accessibleBy($user)->whereKey($shopId)->exists(),403);
    }
    private function same(object $existing,string $hash): void
    {
        if (!hash_equals($existing->request_hash,$hash)) $this->fail(__('This operation key was already used with different data.'));
    }
    private function fail(string $message): never { throw ValidationException::withMessages(['transfer'=>$message]); }
}
