<?php
namespace App\Services;
use App\Models\{CashSession,User,PointOfSale};
use App\Support\SaleOperation as Op;
use Illuminate\Support\Facades\{DB,Gate};
final class CashSupervisionService
{
    public function query(array $shops)
    {
        $activity="GREATEST(s.opened_at, COALESCE((SELECT MAX(o.created_at) FROM orders o WHERE o.cash_session_id=s.id AND o.currency_code='XAF' AND o.sale_state<>'legacy'),s.opened_at), COALESCE((SELECT MAX(CASE WHEN p.receipt_snapshot IS NULL AND p.supplier_id IS NOT NULL THEN DATE_SUB(p.occurred_at,INTERVAL 1 HOUR) ELSE p.occurred_at END) FROM payments p WHERE p.cash_session_id=s.id),s.opened_at), COALESCE((SELECT MAX(m.occurred_at) FROM cash_movements m WHERE m.cash_session_id=s.id),s.opened_at))";
        $base=DB::table('cash_sessions as s')->join('users as u','u.id','=','s.user_id')->join('points_of_sale as shop','shop.id','=','s.point_of_sale_id')->leftJoin('point_of_sale_settings as cfg','cfg.point_of_sale_id','=','s.point_of_sale_id')->leftJoin('reporting_pauses as pause','pause.active_user_id','=','s.user_id')
            ->whereIn('s.point_of_sale_id',$shops)->where('s.state','open')->select('s.*','u.name as cashier','shop.name as shop')->selectRaw($activity.' as last_activity, COALESCE(cfg.orphan_idle_minutes,720) as threshold_minutes, CASE WHEN pause.id IS NULL THEN 0 ELSE 1 END as paused');
        $classified=DB::query()->fromSub($base,'sessions')->select('sessions.*')->selectRaw('CASE WHEN paused=0 AND TIMESTAMPDIFF(MINUTE,last_activity,?) >= threshold_minutes THEN 1 ELSE 0 END as suspected',[now('UTC')->format('Y-m-d H:i:s')]);
        return DB::query()->fromSub($classified,'cash_alerts')->select('cash_alerts.*');
    }
    public function close(User $admin,int $shop,int $id,array $data): CashSession
    {
        abort_unless(!$admin->is_suspended&&$admin->hasRole('Admin')&&$admin->can('cash_session_supervise'),403);Gate::forUser($admin)->authorize('view',PointOfSale::findOrFail($shop));
        if(trim($data['reason']??'')==='')Op::fail('reason','A reason is required.');
        return DB::transaction(function()use($admin,$shop,$id,$data){
            $target=CashSession::whereKey($id)->where('point_of_sale_id',$shop)->firstOrFail();User::whereIn('id',[$admin->id,$target->user_id])->orderBy('id')->lockForUpdate()->get();$target=CashSession::whereKey($id)->lockForUpdate()->firstOrFail();
            $key=Op::key($data);$hash=Op::hash($admin->id,$shop,[$id,$data]);
            if($old=DB::table('cash_session_supervisions')->where('operation_key',$key)->first()){Op::replay($old,$hash);return $target;}
            $status=$this->query([$shop])->where('id',$id)->first();
            if(!$status||!$status->suspected)Op::fail('cash_session','Only an inactive, unpaused open session can be supervised.');
            $closed=app(CashService::class)->close($target->user_id,$shop,$id,['counted_amount'=>$data['counted_amount'],'reason'=>$data['reason']]);
            DB::table('cash_session_supervisions')->insert(['cash_session_id'=>$id,'user_id'=>$admin->id,'counted_amount'=>$closed->counted_amount,'expected_amount'=>$closed->expected_amount,'difference'=>$closed->difference,'reason'=>trim($data['reason']),'operation_key'=>$key,'request_hash'=>$hash,'occurred_at'=>now('UTC')]);return $closed;
        },3);
    }
}
