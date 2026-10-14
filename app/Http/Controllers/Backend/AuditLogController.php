<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PointOfSale;
use App\Support\RoleAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    private function scoped(Request $request)
    {
        abort_unless($request->user()?->can('audit_view'),403);
        abort_unless(Schema::hasTable('audit_logs'),503,__('administration.migration_required'));
        $query=AuditLog::query();
        if(!RoleAssignment::globalManager($request->user())) {
            $shops=$request->user()->pointOfSales()->where('points_of_sale.is_active',true)->wherePivot('is_active',true)->pluck('points_of_sale.id');
            abort_if($shops->isEmpty(),403);
            $query->whereIn('audit_logs.point_of_sale_id',$shops);
        }
        return $query;
    }

    private function withNames($query)
    {
        return $query->leftJoin('users','users.id','=','audit_logs.actor_id')
            ->leftJoin('points_of_sale','points_of_sale.id','=','audit_logs.point_of_sale_id')
            ->select('audit_logs.*','users.name as actor_name','points_of_sale.name as shop_name');
    }

    public function index(Request $request)
    {
        $query=$this->scoped($request);
        $filters=$request->validate([
            'from'=>['nullable','date_format:Y-m-d'], 'until'=>['nullable','date_format:Y-m-d'],
            'actor_id'=>['nullable','integer','min:1'], 'point_of_sale_id'=>['nullable','integer','min:1'],
            'event'=>['nullable','string','max:100'], 'object_type'=>['nullable','string','max:100'],
            'per_page'=>['nullable','integer','in:25,50,100'],
        ]);
        if(!empty($filters['from'])&&!empty($filters['until']) && $filters['from']>$filters['until']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['until'=>__('validation.after_or_equal',['attribute'=>__('administration.until'),'date'=>$filters['from']])]);
        }
        $shops=PointOfSale::query();
        if(!RoleAssignment::globalManager($request->user())) {
            $shops->whereHas('users',fn($q)=>$q->whereKey($request->user()->id)->where('point_of_sale_user.is_active',true))->where('is_active',true);
        }
        $shops=$shops->orderBy('name')->get(['id','name']);
        if(!empty($filters['point_of_sale_id'])) {
            abort_unless($shops->contains('id',(int)$filters['point_of_sale_id']),403);
        }
        $eventChoices=(clone $query)->select('audit_logs.event')->distinct()->orderBy('event')->pluck('event');
        $objectChoices=(clone $query)->select('audit_logs.object_type')->distinct()->orderBy('object_type')->pluck('object_type');
        $actors=(clone $query)->leftJoin('users','users.id','=','audit_logs.actor_id')->whereNotNull('audit_logs.actor_id')
            ->select('audit_logs.actor_id as id')->selectRaw("COALESCE(users.name, CONCAT('#', audit_logs.actor_id)) as name")
            ->distinct()->orderBy('name')->limit(200)->get();
        foreach(['actor_id','point_of_sale_id','event','object_type'] as $field) {
            if(isset($filters[$field]) && $filters[$field]!=='') $query->where('audit_logs.'.$field,$filters[$field]);
        }
        if(!empty($filters['from'])) $query->where('audit_logs.recorded_at','>=',CarbonImmutable::createFromFormat('!Y-m-d',$filters['from'],'Africa/Douala')->utc()->format('Y-m-d H:i:s'));
        if(!empty($filters['until'])) $query->where('audit_logs.recorded_at','<',CarbonImmutable::createFromFormat('!Y-m-d',$filters['until'],'Africa/Douala')->addDay()->utc()->format('Y-m-d H:i:s'));
        $events=$this->withNames($query)->orderByDesc('audit_logs.recorded_at')->orderByDesc('audit_logs.id')->paginate($filters['per_page']??25)->withQueryString();
        $activity=collect();
        if(Schema::hasTable('user_activity')){
            $activityQuery=\Illuminate\Support\Facades\DB::table('user_activity as a')->join('users as u','u.id','=','a.user_id')
                ->leftJoin('points_of_sale as s','s.id','=','a.scope_shop_id');
            if(!RoleAssignment::globalManager($request->user())) $activityQuery->whereIn('a.scope_shop_id',$shops->pluck('id'));
            if(!empty($filters['actor_id']))$activityQuery->where('a.user_id',(int)$filters['actor_id']);
            if(!empty($filters['point_of_sale_id']))$activityQuery->where('a.scope_shop_id',(int)$filters['point_of_sale_id']);
            $activity=$activityQuery->select('a.user_id','a.last_seen_at','u.name','u.is_suspended','s.name as shop')
                ->orderByDesc('a.last_seen_at')->limit(50)->get();
        }
        return view('backend.administration.audit-index',compact('events','eventChoices','objectChoices','shops','filters','actors','activity'));
    }

    public function show(Request $request,string $id)
    {
        $event=$this->withNames($this->scoped($request))->where('audit_logs.id',$id)->firstOrFail();
        return view('backend.administration.audit-show',compact('event'));
    }
}
