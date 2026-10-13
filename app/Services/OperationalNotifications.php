<?php
namespace App\Services;

use App\Jobs\DeliverOperationalNotification;
use App\Models\User;
use App\Support\NotificationCatalog;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;

final class OperationalNotifications
{
    public function ready(): bool { return Schema::hasTable('notification_events'); }

    public function record(string $kind,string $key,?int $shop=null,?int $source=null,array $payload=[],?int $target=null): ?int
    {
        if(!$this->ready())return null; // Compatible source until its additive migration is deployed.
        if(!isset(NotificationCatalog::TYPES[$kind]))throw new \InvalidArgumentException('Unknown notification kind');
        $hash=hash('sha256',$kind.'|'.$key.'|'.($shop??'global'));
        DB::table('notification_events')->insertOrIgnore([
            'event_key'=>$hash,'kind'=>$kind,'point_of_sale_id'=>$shop,'source_id'=>$source,'target_user_id'=>$target,
            'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'available_at'=>now('UTC'),'created_at'=>now('UTC'),'updated_at'=>now('UTC'),
        ]);
        $stored=DB::table('notification_events')->where('event_key',$hash)->first();
        if((int)$stored->source_id!==(int)$source||(int)$stored->target_user_id!==(int)$target||json_decode($stored->payload,true,512,JSON_THROW_ON_ERROR)!=$payload)throw new \LogicException('Notification event key reused with different data');
        return (int)$stored->id;
    }

    public function enabled(User $user,string $kind,string $channel='database'): bool
    {
        $preference=DB::table('notification_preferences')->where('user_id',$user->id)->where('kind',$kind)->where('channel',$channel)->value('enabled');
        return $preference===null?$channel==='database':(bool)$preference;
    }

    public function pump(int $limit=100): int
    {
        if(!$this->ready())return 0;
        $ids=DB::table('notification_events')->whereIn('state',['pending','retry'])->where('available_at','<=',now('UTC'))
            ->where(fn($q)=>$q->whereNull('queued_at')->orWhere('queued_at','<',now('UTC')->subMinutes(10)))->orderBy('id')->limit(max(1,min($limit,500)))->pluck('id');
        foreach($ids as $id)DB::transaction(function()use($id){
            $event=DB::table('notification_events')->where('id',$id)->lockForUpdate()->first();
            if(!in_array($event->state,['pending','retry'],true)||($event->queued_at&&$event->queued_at>now('UTC')->subMinutes(10)->format('Y-m-d H:i:s')))return;
            DB::table('notification_events')->where('id',$id)->update(['queued_at'=>now('UTC'),'updated_at'=>now('UTC')]);
            DeliverOperationalNotification::dispatch($id)->onConnection(config('notifications.connection'))->onQueue(config('notifications.queue'))->afterCommit();
        },3);
        return $ids->count();
    }

    public function deliver(int $id): void
    {
        try{
            DB::transaction(function()use($id){
                // The administrative writers use an exclusive lock on this role.
                // Shared guards allow parallel workers while preventing rights changes mid-delivery.
                DB::table('roles')->where('name','Admin')->where('guard_name','web')->sharedLock()->first();
                $event=DB::table('notification_events')->where('id',$id)->lockForUpdate()->first();
                if(!$event||in_array($event->state,['processed','failed','cancelled'],true))return;
                if(!app(NotificationRelevance::class)->current($event)){
                    DB::table('notification_events')->where('id',$id)->update(['state'=>'cancelled','processed_at'=>now('UTC'),'last_error'=>'source_no_longer_actionable','updated_at'=>now('UTC')]);
                    return;
                }
                User::where('is_suspended',false)->orderBy('id')->chunkById(100,function($users)use($event){
                    foreach($users as $user){
                        $user=User::whereKey($user->id)->sharedLock()->first();
                        if(!$user)continue;
                        if(!NotificationCatalog::allowed($user,$event))continue;
                        $key=hash('sha256',$event->event_key.'|'.$user->id.'|database');
                        if(DB::table('notification_deliveries')->where('delivery_key',$key)->exists())continue;
                        $enabled=$this->enabled($user,$event->kind);$uuid=(string)Str::uuid();
                        DB::table('notification_deliveries')->insert(['notification_event_id'=>$event->id,'user_id'=>$user->id,'channel'=>'database','delivery_key'=>$key,'notification_id'=>$enabled?$uuid:null,'state'=>$enabled?'delivered':'disabled','attempts'=>1,'delivered_at'=>$enabled?now('UTC'):null,'created_at'=>now('UTC'),'updated_at'=>now('UTC')]);
                        if($enabled)DB::table('notifications')->insert(['id'=>$uuid,'type'=>'App\\Notifications\\OperationalAlert','notifiable_type'=>$user->getMorphClass(),'notifiable_id'=>$user->id,'data'=>json_encode(['event_id'=>$event->id,'kind'=>$event->kind,'shop_id'=>$event->point_of_sale_id,'source_id'=>$event->source_id],JSON_THROW_ON_ERROR),'read_at'=>null,'created_at'=>now('UTC'),'updated_at'=>now('UTC')]);
                    }
                });
                DB::table('notification_events')->where('id',$id)->update(['state'=>'processed','attempts'=>$event->attempts+1,'processed_at'=>now('UTC'),'last_error'=>null,'updated_at'=>now('UTC')]);
                DB::table('reporting_runtime')->updateOrInsert(['key'=>'notification_worker_last_run'],['value'=>now('UTC')->toIso8601String()]);
            },3);
        }catch(\Throwable $error){
            // Record only a class code; exception messages can contain credentials or SQL values.
            DB::transaction(function()use($id,$error){$row=DB::table('notification_events')->where('id',$id)->lockForUpdate()->first();if(!$row)return;$attempt=$row->attempts+1;DB::table('notification_events')->where('id',$id)->update(['state'=>$attempt>=(int)config('notifications.max_attempts',5)?'failed':'retry','attempts'=>$attempt,'queued_at'=>null,'available_at'=>now('UTC')->addSeconds(min(3600,30*(2**min($attempt,7)))),'last_error'=>class_basename($error),'updated_at'=>now('UTC')]);},3);
            throw $error;
        }
    }
}
