<?php
namespace App\Services;

use App\Models\{Order,ProductStock};
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

/** Small transactional publishers; no network I/O in business transactions. */
final class NotificationSignals
{
    private function active(): bool
    {
        if(!app(OperationalNotifications::class)->ready())return false;
        if(DB::transactionLevel()<1)throw new \LogicException('Notification signals must share the business transaction');
        return true;
    }

    public function sale(Order $order): void
    {
        if(!$this->active()||$order->currency_code!=='XAF'||$order->sale_state==='legacy')return;
        $threshold=config('system.notification_critical_sale_threshold',config('notifications.critical_sale_threshold'));
        if($threshold!==null&&BigDecimal::of($threshold)->isPositive()&&BigDecimal::of($order->total)->isGreaterThanOrEqualTo($threshold))
            app(OperationalNotifications::class)->record('sale_critical','sale:'.$order->id,$order->point_of_sale_id,$order->id,['amount'=>(string)$order->total,'threshold'=>(string)$threshold]);
    }

    public function purchase(object $purchase): void
    {
        if(!$this->active()||$purchase->currency_code!=='XAF')return;
        app(OperationalNotifications::class)->record('purchase_created','purchase:'.$purchase->id,(int)$purchase->point_of_sale_id,(int)$purchase->id);
    }

    public function pending(int $id,string $kind,?int $seller=null): void
    {
        if(!$this->active()||!in_array($kind,['submitted','completed'],true))return;
        $sale=DB::table('pending_sales')->find($id);
        if($sale)app(OperationalNotifications::class)->record($kind==='submitted'?'cart_pending':'cart_completed','pending:'.$id.':'.$kind,(int)$sale->point_of_sale_id,$id,[],$kind==='completed'?(int)($seller??$sale->seller_user_id):null);
    }

    public function cashClosed(object $session): void
    {
        if(!$this->active()||BigDecimal::of($session->difference??'0')->isZero())return;
        app(OperationalNotifications::class)->record('cash_variance','cash:'.$session->id,(int)$session->point_of_sale_id,(int)$session->id,['difference'=>(string)$session->difference],(int)$session->user_id);
    }

    public function administration(object $audit): void
    {
        if(!$this->active())return;
        $kind=$audit->event==='user.created'?'user_created':(str_starts_with($audit->event,'role.')||in_array($audit->event,['user.access_updated','user.roles_updated'],true)?'role_changed':null);
        $before=(array)$audit->old_values;$after=(array)$audit->new_values;
        if($audit->event==='user.updated'&&($before['roles']??[])!==($after['roles']??[]))$kind='role_changed';
        if($kind)app(OperationalNotifications::class)->record($kind,'audit:'.$audit->id,$audit->point_of_sale_id?(int)$audit->point_of_sale_id:null,(int)$audit->object_id,['audit_id'=>(int)$audit->id]);
    }

    public function summaryDelivery(int $id): void
    {
        if(!$this->active())return;
        $delivery=DB::table('summary_deliveries')->find($id);if(!$delivery)return;
        $summary=DB::table('daily_summaries')->find($delivery->daily_summary_id);if(!$summary)return;
        if($delivery->state==='sent')app(OperationalNotifications::class)->record('summary_sent','summary:'.$summary->id.':'.$delivery->kind,(int)$summary->point_of_sale_id,(int)$summary->id,['delivery_kind'=>$delivery->kind]);
        elseif(in_array($delivery->state,['retry','failed','ambiguous'],true))app(OperationalNotifications::class)->record('email_failed','summary-delivery:'.$id.':'.$delivery->attempts.':'.$delivery->state,null,$id,['state'=>$delivery->state]);
    }

    public function backupFailed(object $run): void
    {
        if(!$this->active()||$run->state!=='failed')return;
        app(OperationalNotifications::class)->record('backup_failed','backup:'.$run->id.':'.($run->attempts??1),null,(int)$run->id);
    }

    public function stockBaseline(ProductStock $stock): void
    {
        if(!$this->active())return;
        $this->stockState($stock,true);
    }

    public function stockChanged(ProductStock $stock): void
    {
        if(!$this->active())return;
        $this->stockState($stock,false);
    }

    private function stockState(ProductStock $stock,bool $initializeOnly): void
    {
        $policy=app(StockPolicyService::class)->effective((int)$stock->point_of_sale_id,(int)$stock->product_id);
        $threshold=$policy['minimum'];
        $quantity=BigDecimal::of(app(StockService::class)->available((int)$stock->point_of_sale_id,(int)$stock->product_id));
        $zone=$quantity->isZero()?'out':($quantity->isLessThanOrEqualTo($threshold)?'low':'normal');
        $key='stock:'.$stock->point_of_sale_id.':'.$stock->product_id;
        $value=$zone.'|'.$threshold.'|'.(int)$policy['alerts_enabled'];
        DB::table('notification_states')->insertOrIgnore(['key'=>$key,'value'=>$value,'generation'=>0,'created_at'=>now('UTC'),'updated_at'=>now('UTC')]);
        $state=DB::table('notification_states')->where('key',$key)->lockForUpdate()->first();
        if($initializeOnly||$state->value===$value)return;
        $prior=explode('|',$state->value)[0];$generation=$state->generation+1;
        DB::table('notification_states')->where('key',$key)->update(['value'=>$value,'generation'=>$generation,'updated_at'=>now('UTC')]);
        $parts=explode('|',$state->value);
        $changedPolicy=($parts[1]??null)!==$threshold || (int)($parts[2]??1)!==(int)$policy['alerts_enabled'];
        $kind=$zone==='out'&&($prior!=='out'||$changedPolicy)?'stock_out':($zone==='low'&&($prior==='normal'||$changedPolicy)?'stock_low':null);
        if($kind&&$policy['alerts_enabled'])app(OperationalNotifications::class)->record($kind,$key.':'.$kind.':'.now('Africa/Douala')->toDateString(),(int)$stock->point_of_sale_id,(int)$stock->product_id,['quantity'=>(string)$quantity,'threshold'=>$threshold,'trigger'=>$changedPolicy?'policy_recheck':'crossing']);
    }
}
