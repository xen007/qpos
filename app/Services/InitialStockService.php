<?php
namespace App\Services;

use App\Models\{User,Product,PointOfSale,ProductStock,StockMovement};
use App\Support\SaleOperation as Op;
use Illuminate\Support\Facades\{DB,Schema,Validator};
use Carbon\CarbonImmutable;

/** Documented startup goods, before operational movements. Never a supplier purchase. */
final class InitialStockService
{
    public function record(int $shopId,int $productId,array $data,int $userId):object
    {
        abort_unless(Schema::hasTable('initial_stock_entries'),503,__('administration.migration_required'));
        Validator::make($data,[
            'operation_key'=>'required|string|max:64','quantity'=>'required|string','unit_cost'=>'required|string',
            'currency_code'=>'required|in:XAF','batch_number'=>'required|string|max:255',
            'expiry_status'=>'required|in:dated,not_applicable','expires_on'=>'nullable|date_format:Y-m-d',
            'received_on'=>'required|date_format:Y-m-d|before_or_equal:'.now('Africa/Douala')->toDateString(),
            'reason'=>'required|string|max:5000','evidence'=>'required|string|max:5000',
        ])->validate();
        return DB::transaction(function()use($shopId,$productId,$data,$userId){
            DB::table('roles')->where('name','Admin')->where('guard_name','web')->sharedLock()->first();
            $actor=User::whereKey($userId)->lockForUpdate()->firstOrFail();
            abort_unless(!$actor->is_suspended,403);
            // Lock the product before authorization creates an MVCC read view.
            // The next replay/classification reads must observe the preceding writer.
            $product=Product::whereKey($productId)->lockForUpdate()->firstOrFail();
            abort_unless($actor->can('stock_opening_approve')&&PointOfSale::accessibleBy($actor)->whereKey($shopId)->exists(),403);
            $key=Op::key($data);$hash=Op::hash($userId,$shopId,[$productId,$data]);
            if($old=DB::table('initial_stock_entries')->where('operation_key',$key)->lockForUpdate()->first()){Op::replay($old,$hash);return $old;}
            if(!$product->status)Op::fail('product_id','Select an active product.');
            if(DB::table('inventory_items')->where('active_scope',$shopId.':'.$productId)->exists())
                Op::fail('product_id','Finish or cancel this product inventory before entering startup stock.');
            $prior=DB::table('initial_stock_entries')->where('point_of_sale_id',$shopId)->where('product_id',$productId)->orderBy('id')->lockForUpdate()->get();
            if($prior->isNotEmpty()){
                $first=StockMovement::where('point_of_sale_id',$shopId)->where('correlation_key',$prior->first()->opening_key)->firstOrFail();
                if($first->occurred_at->format('Y-m-d')!==$data['received_on']) Op::fail('received_on','Use the same startup date for all initial lots of this product in this store.');
            }
            if(DB::table('initial_stock_entries as e')->join('stock_opening_approvals as a','a.id','=','e.approval_id')
                ->join('product_batches as b','b.id','=','a.product_batch_id')->where('e.point_of_sale_id',$shopId)->where('e.product_id',$productId)
                ->where('b.batch_number',trim($data['batch_number']))->exists())
                Op::fail('batch_number','This startup lot is already recorded in this store.');
            $allowed=$prior->flatMap(fn($entry)=>[$entry->opening_key,'initial-approve-'.substr(hash('sha256',$entry->operation_key),0,40)])->all();
            if(StockMovement::where('point_of_sale_id',$shopId)->where('product_id',$productId)->whereNotIn('correlation_key',$allowed)->exists())
                Op::fail('product_id','Startup stock is only allowed before operational movements. Use purchases, transfers or an inventory for later operations.');
            if($prior->isEmpty()){
                $balance=ProductStock::where('point_of_sale_id',$shopId)->where('product_id',$productId)->first();
                if($balance && collect(['saleable_quantity','unsaleable_quantity','in_transit_quantity','unallocated_opening_quantity'])
                    ->contains(fn($field)=>\Brick\Math\BigDecimal::of($balance->$field)->isPositive()))
                    Op::fail('product_id','Existing stock must be reconciled through its original documents.');
            }
            $stock=app(StockService::class);
            $openingKey='initial-open-'.substr(hash('sha256',$key),0,40);
            $approvalKey='initial-approve-'.substr(hash('sha256',$key),0,40);
            $stock->increase($shopId,$productId,$data['quantity'],[
                'type'=>'opening','bucket'=>'unallocated_opening','correlation_key'=>$openingKey,
                'user_id'=>$userId,'reason'=>trim($data['reason']),
                'occurred_at'=>CarbonImmutable::createFromFormat('!Y-m-d',$data['received_on'],'Africa/Douala'),
            ]);
            $approval=$stock->approveOpening($shopId,$productId,[
                ...array_intersect_key($data,array_flip(['quantity','unit_cost','currency_code','batch_number','expiry_status','expires_on','reason','evidence'])),
                'operation_key'=>$approvalKey,
            ],$userId);
            $id=DB::table('initial_stock_entries')->insertGetId([
                'point_of_sale_id'=>$shopId,'product_id'=>$productId,'user_id'=>$userId,
                'approval_id'=>$approval->id,'opening_key'=>$openingKey,'operation_key'=>$key,'request_hash'=>$hash,'recorded_at'=>now('UTC'),
            ]);
            app(AdministrationAudit::class)->record('stock.initial_documented','initial_stock',$id,[],['changed_keys'=>['quantity','lot','cost','expiry','evidence']],$shopId,$data['reason']);
            return DB::table('initial_stock_entries')->find($id);
        },3);
    }
}
