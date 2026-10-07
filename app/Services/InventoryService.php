<?php
namespace App\Services;

use App\Models\{BatchStock,PointOfSale,Product,ProductStock,StockMovement,User};
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InventoryService
{
    public function create(int $shopId,array $productIds,string $reason,string $key,int $userId): object
    {
        return DB::transaction(function () use ($shopId,$productIds,$reason,$key,$userId) {
            $this->authorize($shopId,$userId);
            $ids=array_values(array_unique(array_map('intval',$productIds))); sort($ids);
            if (!$ids || trim($reason)==='' || $key==='' || strlen($key)>64) $this->fail(__('Choose an inventory scope and a reason.'));
            $products=Product::whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();
            if ($products->count()!==count($ids) || $products->contains(fn($p)=>$p->allows_fractional===null)) $this->fail(__('Configure all product quantity rules before counting.'));
            $hash=hash('sha256',json_encode([$shopId,$ids,$reason,$userId],JSON_THROW_ON_ERROR));
            $existing=DB::table('inventories')->where('operation_key',$key)->first();
            if ($existing) {
                if (!hash_equals($existing->request_hash,$hash)) $this->fail(__('This operation key was already used with different data.'));
                return $existing;
            }
            foreach ($ids as $id) if (DB::table('inventory_items')->where('active_scope',$shopId.':'.$id)->exists()) $this->fail(__('An inventory already covers this product in this shop.'));
            $id=DB::table('inventories')->insertGetId([
                'point_of_sale_id'=>$shopId,'user_id'=>$userId,'operation_key'=>$key,'request_hash'=>$hash,'reason'=>$reason,
                'status'=>'counting','started_at'=>now('Africa/Douala'),'created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach ($ids as $productId) DB::table('inventory_items')->insert([
                'inventory_id'=>$id,'product_id'=>$productId,'active_scope'=>$shopId.':'.$productId,'created_at'=>now(),'updated_at'=>now(),
            ]);
            return DB::table('inventories')->find($id);
        },3);
    }

    /** Product lock is the committed journal fence shared with checkout. */
    public function beginCount(int $id,int $itemId,int $userId): object
    {
        return DB::transaction(function () use ($id,$itemId,$userId) {
            [$inventory,$item]=$this->locked($id,$itemId,$userId);
            Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
            $snapshot=$this->snapshot($inventory->point_of_sale_id,$item->product_id);
            DB::table('inventory_items')->where('id',$itemId)->update([
                'count_watermark'=>$this->watermark($inventory->point_of_sale_id,$item->product_id),
                'count_started_at'=>now('Africa/Douala'),'counted_at'=>null,
                'count_values'=>json_encode(['baseline'=>$snapshot,'counts'=>null],JSON_THROW_ON_ERROR),'updated_at'=>now(),
            ]);
            return DB::table('inventory_items')->find($itemId);
        },3);
    }

    public function recordCount(int $id,int $itemId,array $counts,int $userId): void
    {
        DB::transaction(function () use ($id,$itemId,$counts,$userId) {
            [$inventory,$item]=$this->locked($id,$itemId,$userId);
            $product=Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
            if (!$item->count_started_at || !$item->count_values) $this->fail(__('Start this physical count before entering quantities.'));
            $values=json_decode($item->count_values,true,512,JSON_THROW_ON_ERROR);
            $keys=array_keys($values['baseline']); $provided=array_keys($counts); sort($keys); sort($provided);
            if ($keys!==$provided) $this->fail(__('Enter every lot and bucket explicitly. Blank is not zero.'));
            foreach ($counts as $key=>$value) {
                $amount=QuantityDecimal::parse($value);
                if ($amount->isPositive()) QuantityDecimal::toBase($value,'1',(bool)$product->allows_fractional);
                $counts[$key]=(string)$amount->toScale(6);
                if ($key==='0:unallocated_opening' && $amount->isGreaterThan($values['baseline'][$key]))
                    $this->fail(__('Unidentified surplus requires evidence; inventory cannot add or approve opening stock.'));
            }
            if (BigDecimal::of($values['baseline']['0:unallocated_opening'])->isPositive()) {
                $byLot=[];
                foreach ($counts as $key=>$count) {
                    [$lotId,$bucket]=explode(':',$key);
                    if (!in_array($bucket,['saleable','unsaleable'],true)) continue;
                    $byLot[$lotId] ??= ['before'=>BigDecimal::zero(),'count'=>BigDecimal::zero()];
                    $byLot[$lotId]['before']=$byLot[$lotId]['before']->plus($values['baseline'][$key]);
                    $byLot[$lotId]['count']=$byLot[$lotId]['count']->plus($count);
                }
                // Reclassification inside an evidenced lot is allowed; a net
                // gain in any lot cannot be sourced from blocked openings.
                foreach ($byLot as $lot) if ($lot['count']->isGreaterThan($lot['before']))
                    $this->fail(__('A product with blocked openings cannot receive inventory surplus. Validate its provenance explicitly first.'));
            }
            if ($item->counted_at) {
                if ($values['counts']!==$counts) $this->fail(__('Start a new count to change recorded quantities.'));
                return;
            }
            if ($this->watermark($inventory->point_of_sale_id,$item->product_id)!==(int)$item->count_watermark)
                $this->fail(__('Stock moved during counting. Start and recount this product.'));
            $values['counts']=$counts;
            DB::table('inventory_items')->where('id',$itemId)->update(['counted_at'=>now('Africa/Douala'),'count_values'=>json_encode($values,JSON_THROW_ON_ERROR),'updated_at'=>now()]);
        },3);
    }

    public function validate(int $id,int $userId): object
    {
        return DB::transaction(function () use ($id,$userId) {
            $inventory=DB::table('inventories')->where('id',$id)->lockForUpdate()->firstOrFail();
            $this->authorize($inventory->point_of_sale_id,$userId);
            if ($inventory->status==='validated') return $inventory;
            if ($inventory->status!=='counting') $this->fail(__('This inventory is closed.'));
            $items=DB::table('inventory_items')->where('inventory_id',$id)->orderBy('product_id')->lockForUpdate()->get();
            Product::whereIn('id',$items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get();
            foreach ($items as $item) {
                if (!$item->counted_at) $this->fail(__('Every product must have a completed count.'));
                $values=json_decode($item->count_values,true,512,JSON_THROW_ON_ERROR); $changes=[];
                // Reconcile later journal entries against the count baseline, never
                // overwrite a balance with yesterday's count (e.g. 98 - sale 3 = 95).
                $current=$this->snapshot($inventory->point_of_sale_id,$item->product_id);
                foreach ($values['counts'] as $key=>$count) {
                    $delta=BigDecimal::of($count)->minus($values['baseline'][$key]);
                    if ($delta->isZero()) continue;
                    $target=BigDecimal::of($current[$key] ?? '0')->plus($delta);
                    if ($target->isNegative()) $this->fail(__('Later stock movements conflict with this count. Recount the product.'));
                    [$batchId,$bucket]=explode(':',$key);
                    $move=app(StockService::class)->correctBucket($inventory->point_of_sale_id,$item->product_id,(int)$batchId ?: null,
                        $bucket,(string)$delta,'inventory_adjustment','inventory:'.$id.':'.$item->id,$inventory->reason,$userId,count($changes)+1);
                    $changes[]=['movement_id'=>$move->id,'key'=>$key,'delta'=>(string)$delta,'later_movements'=>(string)BigDecimal::of($current[$key] ?? '0')->minus($values['baseline'][$key]),'target'=>(string)$target];
                }
                DB::table('inventory_items')->where('id',$item->id)->update(['active_scope'=>null,'adjustments'=>json_encode($changes,JSON_THROW_ON_ERROR),'updated_at'=>now()]);
            }
            DB::table('inventories')->where('id',$id)->update(['status'=>'validated','validated_by'=>$userId,'validated_at'=>now('Africa/Douala'),'updated_at'=>now()]);
            return DB::table('inventories')->find($id);
        },3);
    }

    public function cancel(int $id,int $userId): void
    {
        DB::transaction(function () use ($id,$userId) {
            $inventory=DB::table('inventories')->where('id',$id)->lockForUpdate()->firstOrFail(); $this->authorize($inventory->point_of_sale_id,$userId);
            if ($inventory->status==='cancelled') return;
            if ($inventory->status!=='counting') $this->fail(__('A validated inventory cannot be erased.'));
            DB::table('inventory_items')->where('inventory_id',$id)->update(['active_scope'=>null,'updated_at'=>now()]);
            DB::table('inventories')->where('id',$id)->update(['status'=>'cancelled','updated_at'=>now()]);
        },3);
    }

    private function locked(int $id,int $itemId,int $userId): array
    {
        $inventory=DB::table('inventories')->where('id',$id)->lockForUpdate()->firstOrFail(); $this->authorize($inventory->point_of_sale_id,$userId);
        if ($inventory->status!=='counting') $this->fail(__('This inventory is closed.'));
        return [$inventory,DB::table('inventory_items')->where('inventory_id',$id)->where('id',$itemId)->lockForUpdate()->firstOrFail()];
    }
    private function watermark(int $shopId,int $productId): int
    {
        return (int)StockMovement::where('point_of_sale_id',$shopId)->where('product_id',$productId)->max('id');
    }
    private function snapshot(int $shopId,int $productId): array
    {
        $stock=ProductStock::where('point_of_sale_id',$shopId)->where('product_id',$productId)->first();
        $snapshot=['0:unallocated_opening'=>(string)($stock?->unallocated_opening_quantity ?? '0.000000')];
        $assigned=['saleable'=>BigDecimal::zero(),'unsaleable'=>BigDecimal::zero()];
        foreach (BatchStock::where('point_of_sale_id',$shopId)->whereHas('batch',fn($q)=>$q->where('product_id',$productId))->orderBy('product_batch_id')->get() as $lot) {
            foreach (array_keys($assigned) as $bucket) {
                $field=$bucket.'_quantity'; $snapshot[$lot->product_batch_id.':'.$bucket]=(string)$lot->$field;
                $assigned[$bucket]=$assigned[$bucket]->plus($lot->$field);
            }
        }
        foreach ($assigned as $bucket=>$sum) {
            $field=$bucket.'_quantity'; $lotless=BigDecimal::of($stock?->$field ?? '0')->minus($sum);
            if (!$lotless->isZero()) $snapshot['0:'.$bucket]=(string)$lotless;
        }
        return $snapshot;
    }
    private function authorize(int $shopId,int $userId): void
    {
        $user=User::findOrFail($userId);
        abort_unless($user->can('stock_inventory') && PointOfSale::accessibleBy($user)->whereKey($shopId)->exists(),403);
    }
    private function fail(string $message): never { throw ValidationException::withMessages(['inventory'=>$message]); }
}
