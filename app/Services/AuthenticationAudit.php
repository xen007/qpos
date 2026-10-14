<?php
namespace App\Services;
use App\Models\{User,PointOfSale,AuditLog};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
final class AuthenticationAudit
{
    public function record(string $event,User $user):void
    {
        if(!Schema::hasTable('audit_logs'))return; // Compatible during additive deployment.
        $r=request();$shop=null;
        $preferred=$r->hasSession()?$r->session()->get('active_point_of_sale_id'):null;
        $candidate=$user->fixed_point_of_sale_id ?: $preferred ?: $user->preferred_point_of_sale_id;
        if($candidate&&PointOfSale::accessibleBy($user)->whereKey((int)$candidate)->exists())$shop=(int)$candidate;
        elseif($user->can('point_of_sale_access')){
            $choices=PointOfSale::accessibleBy($user)->limit(2)->pluck('id');
            if($choices->count()===1)$shop=(int)$choices->first();
        }
        DB::transaction(function()use($event,$user,$shop){
            AuditLog::create([
                'actor_id'=>$user->id,'point_of_sale_id'=>$shop,'event'=>$event,'object_type'=>'user','object_id'=>$user->id,
                'old_values'=>[],'new_values'=>[],'result'=>'success','reason'=>null,'correlation_id'=>(string)Str::uuid(),
                'occurred_at'=>now('UTC'),'recorded_at'=>now('UTC'),
            ]);
        },3);
    }
}
