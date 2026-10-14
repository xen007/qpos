<?php
namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\{DB,Schema};
use Brick\Math\BigDecimal;

/** Suppress alerts already resolved before the worker processes them. */
final class NotificationRelevance
{
    public function current(object $event): bool
    {
        if($event->point_of_sale_id&&!DB::table('points_of_sale')->where('id',$event->point_of_sale_id)->where('is_active',true)->exists())return false;
        $payload=json_decode($event->payload,true,512,JSON_THROW_ON_ERROR);
        if($event->kind==='sale_critical'){
            $threshold=config('system.notification_critical_sale_threshold',config('notifications.critical_sale_threshold'));
            $order=Order::find($event->source_id);
            return $threshold!==null&&$order&&$order->currency_code==='XAF'&&$order->sale_state!=='legacy'&&BigDecimal::of($order->total)->isGreaterThanOrEqualTo($threshold);
        }
        if(in_array($event->kind,['stock_low','stock_out'],true)){
            $qty=BigDecimal::of(app(StockService::class)->available((int)$event->point_of_sale_id,(int)$event->source_id));
            $policy=app(StockPolicyService::class)->effective((int)$event->point_of_sale_id,(int)$event->source_id);
            return $policy['alerts_enabled']&&($event->kind==='stock_out'?$qty->isZero():($qty->isPositive()&&$qty->isLessThanOrEqualTo($policy['minimum'])));
        }
        if($event->kind==='debt_overdue'){
            $order=Order::find($event->source_id);return $order&&$order->currency_code==='XAF'&&$order->sale_state!=='legacy'&&BigDecimal::of($order->due)->isPositive()&&$order->due_date&&$order->due_date<now('Africa/Douala')->toDateString();
        }
        if(in_array($event->kind,['cart_pending','cart_completed'],true)){
            $sale=DB::table('pending_sales')->find($event->source_id);if(!$sale)return false;
            if($event->kind==='cart_completed')return $sale->state==='completed'&&(int)$sale->seller_user_id===(int)$event->target_user_id;
            return $sale->state==='pending'&&$sale->expires_at>now('UTC')->format('Y-m-d H:i:s')&&app(ShopWorkflowSettings::class)->get((int)$sale->point_of_sale_id)['pending_sale_enabled'];
        }
        if($event->kind==='expiry'){
            $lot=DB::table('product_batches')->find($event->source_id);if(!$lot||$lot->expiry_status!=='dated'||$lot->expires_on!==($payload['expires_on']??null))return false;
            $qty=DB::table('batch_stock')->where('product_batch_id',$lot->id)->where('point_of_sale_id',$event->point_of_sale_id)->selectRaw('SUM(saleable_quantity + unsaleable_quantity) AS quantity')->value('quantity');
            $days=(int)\Carbon\CarbonImmutable::now('Africa/Douala')->startOfDay()->diffInDays(\Carbon\CarbonImmutable::parse($lot->expires_on,'Africa/Douala'),false);
            $tier=match(true){$days<0=>0,$days<=7=>7,$days<=30=>30,$days<=90=>90,default=>null};
            return BigDecimal::of($qty??'0')->isPositive()&&$tier===($payload['threshold']??null);
        }
        if($event->kind==='session_orphan')return app(CashSupervisionService::class)->query([(int)$event->point_of_sale_id])->where('id',$event->source_id)->where('suspected',1)->where('paused',0)->exists();
        if($event->kind==='purchase_created')return DB::table('purchases')->where('id',$event->source_id)->where('currency_code','XAF')->whereNull('cancelled_at')->exists();
        if($event->kind==='backup_failed')return Schema::hasTable('backup_runs')&&DB::table('backup_runs')->where('id',$event->source_id)->where(fn($q)=>$q->where('state','failed')->orWhere('cloud_state','failed'))->exists();
        return true;
    }
}
