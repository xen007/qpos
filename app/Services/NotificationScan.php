<?php
namespace App\Services;

use App\Models\{Order,PointOfSale,ProductStock};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class NotificationScan
{
    private function cursor(string $kind): int
    {
        return (int)DB::table('reporting_runtime')->where('key','notification_scan_'.$kind)->value('value');
    }
    private function saveCursor(string $kind,int $id): void
    {
        DB::table('reporting_runtime')->updateOrInsert(['key'=>'notification_scan_'.$kind],['value'=>(string)$id]);
    }

    /** Bounded round-robin scans. Hooks handle live writes; scans catch time-based changes. */
    public function run(int $limit=500): array
    {
        if(!app(OperationalNotifications::class)->ready())return ['unavailable'=>true];
        $limit=max(1,min($limit,1000));$shops=PointOfSale::active()->pluck('id')->all();$counts=[];
        $stocks=ProductStock::whereIn('point_of_sale_id',$shops)->whereHas('product',fn($q)=>$q->where('status',true))->where('id','>',$this->cursor('stock'))->orderBy('id')->limit($limit)->get();
        foreach($stocks as $stock)DB::transaction(function()use($stock){
            $current=ProductStock::whereKey($stock->id)->lockForUpdate()->first();
            if($current)app(NotificationSignals::class)->stockChanged($current);
        },3);
        $counts['stock']=$stocks->count();$this->saveCursor('stock',$stocks->count()===$limit?(int)$stocks->last()->id:0);

        $today=CarbonImmutable::now('Africa/Douala')->startOfDay();
        $expiry=DB::table('batch_stock as s')->join('product_batches as b','b.id','=','s.product_batch_id')->join('products as p','p.id','=','b.product_id')->whereIn('s.point_of_sale_id',$shops)->where('p.status',true)->where('b.expiry_status','dated')->whereNotNull('b.expires_on')->where('b.expires_on','<=',$today->addDays(90)->toDateString())
            ->where(fn($q)=>$q->where('s.saleable_quantity','>',0)->orWhere('s.unsaleable_quantity','>',0))->where('s.id','>',$this->cursor('expiry'))->orderBy('s.id')->limit($limit)->get(['s.id','s.point_of_sale_id','b.id as batch_id','b.expires_on']);
        foreach($expiry as $lot)DB::transaction(function()use($lot,$today){
            $current=DB::table('batch_stock as s')->join('product_batches as b','b.id','=','s.product_batch_id')->where('s.id',$lot->id)->lockForUpdate()->first(['s.point_of_sale_id','s.saleable_quantity','s.unsaleable_quantity','b.id as batch_id','b.expiry_status','b.expires_on']);
            if(!$current||$current->expiry_status!=='dated'||!$current->expires_on||($current->saleable_quantity<=0&&$current->unsaleable_quantity<=0))return;
            $days=(int)$today->diffInDays(CarbonImmutable::parse($current->expires_on,'Africa/Douala'),false);
            $threshold=match(true){$days<0=>0,$days<=7=>7,$days<=30=>30,$days<=90=>90,default=>null};
            if($threshold!==null)app(OperationalNotifications::class)->record('expiry','lot:'.$current->batch_id.':'.$current->expires_on.':'.$threshold,(int)$current->point_of_sale_id,(int)$current->batch_id,['threshold'=>$threshold,'expires_on'=>$current->expires_on]);
        },3);
        $counts['expiry']=$expiry->count();$this->saveCursor('expiry',$expiry->count()===$limit?(int)$expiry->last()->id:0);

        $debts=Order::whereIn('point_of_sale_id',$shops)->where('currency_code','XAF')->where('sale_state','<>','legacy')->where('due','>',0)->where('due_date','<',$today->toDateString())->where('id','>',$this->cursor('debt'))->orderBy('id')->limit($limit)->get();
        foreach($debts as $debt)DB::transaction(function()use($debt,$today){
            $current=Order::whereKey($debt->id)->lockForUpdate()->first();
            if($current&&$current->currency_code==='XAF'&&$current->sale_state!=='legacy'&&$current->due>0&&$current->due_date<$today->toDateString())app(OperationalNotifications::class)->record('debt_overdue','debt:'.$current->id.':'.$current->due_date,(int)$current->point_of_sale_id,(int)$current->id);
        },3);
        $counts['debt']=$debts->count();$this->saveCursor('debt',$debts->count()===$limit?(int)$debts->last()->id:0);

        $orphans=app(CashSupervisionService::class)->query($shops)->where('suspected',1)->where('paused',0)->where('id','>',$this->cursor('orphan'))->orderBy('id')->limit($limit)->get();
        foreach($orphans as $session)DB::transaction(function()use($session){
            // Use the same user/session lock order as cash operations.
            DB::table('users')->where('id',$session->user_id)->lockForUpdate()->first();
            $cash=DB::table('cash_sessions')->where('id',$session->id)->lockForUpdate()->first();
            if(!$cash||$cash->state!=='open')return;
            $current=app(CashSupervisionService::class)->query([(int)$session->point_of_sale_id])->where('id',$session->id)->where('suspected',1)->where('paused',0)->first();
            if($current)app(OperationalNotifications::class)->record('session_orphan','session:'.$current->id.':'.$current->last_activity,(int)$current->point_of_sale_id,(int)$current->id);
        },3);
        $counts['orphan']=$orphans->count();$this->saveCursor('orphan',$orphans->count()===$limit?(int)$orphans->last()->id:0);
        DB::table('reporting_runtime')->updateOrInsert(['key'=>'notification_scheduler_last_run'],['value'=>now('UTC')->toIso8601String()]);
        return $counts;
    }
}
