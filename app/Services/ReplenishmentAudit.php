<?php
namespace App\Services;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
final class ReplenishmentAudit
{
    public function record(string $event, string|int $id, ?int $shop, array $before, array $after, string $reason, int $actor): void
    {
        if(DB::transactionLevel()<1) throw new \LogicException('Audit requires the same transaction.');
        $safe=fn(array $v)=>array_intersect_key($v,array_flip(['scope_key','minimum','optimal','maximum','alerts_enabled','maximum_enabled','hide_out_of_stock','status','reference','items','settings','supplier_id','product_id','product_unit_id','delay_days','minimum_order','order_multiple','estimated_cost','preferred','purchase_id','quantity','closed_quantity','snapshot','received_quantity']));
        AuditLog::create(['actor_id'=>$actor,'point_of_sale_id'=>$shop,'event'=>$event,'object_type'=>'replenishment','object_id'=>(string)$id,'old_values'=>$safe($before),'new_values'=>$safe($after),'result'=>'success','reason'=>$reason,'correlation_id'=>(string)Str::uuid(),'occurred_at'=>now('UTC'),'recorded_at'=>now('UTC')]);
    }
}
