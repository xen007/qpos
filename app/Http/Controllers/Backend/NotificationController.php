<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\{PointOfSale,User};
use App\Services\OperationalNotifications;
use App\Support\{NotificationCatalog,RoleAssignment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class NotificationController extends Controller
{
    private function user(Request $r): User
    {
        $user=$r->user()?->fresh();abort_unless($user&&!$user->is_suspended&&$user->getRoleNames()->isNotEmpty(),403);
        abort_unless(app(OperationalNotifications::class)->ready(),503);
        return $user;
    }

    private function kinds(User $user): array
    {
        $shop=$user->can('point_of_sale_access')?($user->fixed_point_of_sale_id?:PointOfSale::accessibleBy($user)->value('points_of_sale.id')):null;$result=[];
        foreach(array_keys(NotificationCatalog::TYPES) as $kind){
            $event=(object)['kind'=>$kind,'point_of_sale_id'=>$shop,'target_user_id'=>$user->id];
            if(NotificationCatalog::allowed($user,$event))$result[]=$kind;
        }
        return $result;
    }

    private function visible(User $user)
    {
        $shops=$user->can('point_of_sale_access')?PointOfSale::accessibleBy($user)->pluck('points_of_sale.id')->all():[];
        $query=DB::table('notifications as n')->join('notification_deliveries as d','d.notification_id','=','n.id')->join('notification_events as e','e.id','=','d.notification_event_id')
            ->where('n.notifiable_type',$user->getMorphClass())->where('n.notifiable_id',$user->id)->where('d.user_id',$user->id)->where('d.channel','database')->where('d.state','delivered')->whereIn('e.kind',$this->kinds($user))
            ->where(function($q)use($user,$shops){$q->whereIn('e.point_of_sale_id',$shops);if(RoleAssignment::globalManager($user))$q->orWhereNull('e.point_of_sale_id');})
            ->where(fn($q)=>$q->where('e.kind','<>','cart_completed')->orWhere('e.target_user_id',$user->id));
        if(!$user->can('cash_session_supervise')&&!($user->hasRole('shop_admin')&&$user->can('reports_summary')))$query->where(fn($q)=>$q->where('e.kind','<>','cash_variance')->orWhere('e.target_user_id',$user->id));
        if($user->fixed_point_of_sale_id){$operational=['cart_pending'];if(!$user->can('stock_view'))$operational[]='stock_low';$query->where(fn($q)=>$q->whereNotIn('e.kind',$operational)->orWhere('e.point_of_sale_id',$user->fixed_point_of_sale_id));}
        return $query;
    }

    public function feed(Request $r)
    {
        $user=$this->user($r);$v=$r->validate(['before'=>'nullable|integer|min:1']);
        $query=$this->visible($user);$unread=(clone $query)->whereNull('n.read_at')->count();
        $rows=$query->when(isset($v['before']),fn($q)=>$q->where('d.id','<',$v['before']))->orderByDesc('d.id')->limit(31)->get(['n.id','n.read_at','n.created_at','d.id as cursor','e.id as event_id','e.kind','e.point_of_sale_id','e.source_id','e.target_user_id']);
        $more=$rows->count()>30;$rows=$rows->take(30);
        return response()->json(['unread'=>$unread,'rows'=>$rows->map(fn($row)=>['id'=>$row->id,'title'=>__('notifications.types.'.$row->kind),'severity'=>NotificationCatalog::TYPES[$row->kind][1],'reference'=>$row->source_id?'#'.$row->source_id:null,'shop_id'=>$row->point_of_sale_id,'url'=>NotificationCatalog::link($row,$user),'read'=>(bool)$row->read_at,'created_at'=>$row->created_at])->values(),'next'=>$more?$rows->last()->cursor:null])->header('Cache-Control','no-store');
    }

    public function read(Request $r,string $id)
    {
        $user=$this->user($r);$row=$this->visible($user)->where('n.id',$id)->first(['n.id']);abort_unless($row,404);
        DB::table('notifications')->where('id',$row->id)->where('notifiable_id',$user->id)->update(['read_at'=>now('UTC'),'updated_at'=>now('UTC')]);return response()->json(['ok'=>true]);
    }

    public function readAll(Request $r)
    {
        $user=$this->user($r);$ids=$this->visible($user)->select('n.id');
        // Derived query avoids MySQL's target-table restriction.
        DB::table('notifications')->whereIn('id',DB::query()->fromSub($ids,'visible_ids')->select('id'))->where('notifiable_id',$user->id)->whereNull('read_at')->update(['read_at'=>now('UTC'),'updated_at'=>now('UTC')]);
        return response()->json(['ok'=>true]);
    }

    public function preferences(Request $r)
    {
        $user=$this->user($r);$kinds=$this->kinds($user);$notifications=app(OperationalNotifications::class);
        $external=app(\App\Services\ExternalAlertService::class);
        return view('backend.notifications.preferences',compact('kinds','notifications','user','external'));
    }

    public function savePreferences(Request $r)
    {
        $user=$this->user($r);$v=$r->validate(['enabled'=>'nullable|array|max:15','enabled.*'=>'string|distinct','external'=>'nullable|array:email,sms,whatsapp','external.*'=>'nullable|array|max:5','external.*.*'=>'string|distinct']);$kinds=$this->kinds($user);$enabled=$v['enabled']??[];
        abort_unless(count(array_diff($enabled,$kinds))===0,403);
        $external=app(\App\Services\ExternalAlertService::class);
        $channels=$external->availableChannels($user);
        $externalKinds=array_values(array_intersect($kinds,\App\Services\ExternalAlertService::KINDS));
        foreach($v['external']??[] as $channel=>$selected)abort_unless(in_array($channel,$channels,true)&&!array_diff($selected,$externalKinds),403);
        DB::transaction(function()use($user,$channels,$externalKinds,$v){
            foreach($channels as $channel)foreach($externalKinds as $kind)DB::table('notification_preferences')->updateOrInsert(
                ['user_id'=>$user->id,'kind'=>$kind,'channel'=>$channel],
                ['enabled'=>in_array($kind,$v['external'][$channel]??[],true),'updated_at'=>now('UTC'),'created_at'=>now('UTC')]);
        });
        DB::transaction(function()use($user,$kinds,$enabled){foreach($kinds as $kind)DB::table('notification_preferences')->updateOrInsert(['user_id'=>$user->id,'kind'=>$kind,'channel'=>'database'],['enabled'=>in_array($kind,$enabled,true),'updated_at'=>now('UTC'),'created_at'=>now('UTC')]);});
        return back()->with('success',__('notifications.saved'));
    }
}
