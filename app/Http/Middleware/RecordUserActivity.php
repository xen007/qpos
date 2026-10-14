<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
final class RecordUserActivity
{
    public function handle(Request $r,Closure $next)
    {
        $response=$next($r);
        $user=$r->user();
        if($response->getStatusCode()<400&&$user&&!$user->is_suspended&&\App\Support\OperationalSchema::has('user_activity')){
            $scope=(int)($r->attributes->get('point_of_sale')?->id??0);
            $key=['user_id'=>$user->id,'scope_shop_id'=>$scope];
            $now=now('UTC');$cutoff=$now->copy()->subMinute();
            // At most one timestamp write per minute and user/store; no session identifier.
            $existing=DB::table('user_activity')->where($key)->value('last_seen_at');
            if(!$existing)DB::table('user_activity')->insertOrIgnore($key+['last_seen_at'=>$now]);
            elseif($existing<$cutoff->format('Y-m-d H:i:s'))DB::table('user_activity')->where($key)->where('last_seen_at','<',$cutoff)->update(['last_seen_at'=>$now]);
        }
        return $response;
    }
}
