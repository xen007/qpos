<?php
namespace App\Services;

use App\Jobs\DeliverExternalAlert;
use App\Mail\OperationalAlertMail;
use App\Models\User;
use App\Support\{NotificationCatalog,RoleAssignment};
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{Crypt,DB,Http,Mail,Schema};

final class ExternalAlertService
{
    public const KINDS=['sale_critical','stock_out','cash_variance','backup_failed','session_orphan'];
    public const CHANNELS=['email','sms','whatsapp'];
    public function ready(): bool { return \App\Support\OperationalSchema::has('notification_outbound'); }
    public function settings(): array { return (array)config('system.external_alerts',[]); }

    public function channelReady(string $channel,string $locale='fr'): bool {
        if($channel==='email')return app(SummaryMailApproval::class)->transportReady();
        $c=(array)config('notifications.twilio',[]);
        if(!preg_match('/^AC[0-9a-fA-F]{32}$/',$c['account_sid']??'')||empty($c['auth_token']))return false;
        if(!preg_match('/^\+[1-9][0-9]{6,14}$/',$c[$channel.'_from']??''))return false;
        return $channel!=='whatsapp'||preg_match('/^HX[0-9a-fA-F]{32}$/',$c['templates'][$locale]??'')===1;
    }
    public function preference(User $user,string $kind,string $channel): bool {
        $v=DB::table('notification_preferences')->where('user_id',$user->id)->where('kind',$kind)->where('channel',$channel)->value('enabled');
        return $v===null||(bool)$v; // Global destination consent is required separately.
    }
    public function availableChannels(User $user): array {
        $s=$this->settings();
        if(!RoleAssignment::globalManager($user)||!$user->can('system_monitor')||(int)($s['owner_id']??0)!==$user->id)return [];
        return array_values(array_filter(self::CHANNELS,fn($c)=>!empty($s[$c]['enabled'])&&!empty($s[$c]['destinations'])));
    }
    private function approved(User $user,object $event,string $channel,string $destination): bool {
        $s=$this->settings();
        return !$user->is_suspended&&RoleAssignment::globalManager($user)&&$user->can('system_monitor')
            &&(int)($s['owner_id']??0)===$user->id&&in_array($event->kind,self::KINDS,true)
            &&in_array($event->kind,$s['kinds']??[],true)&&NotificationCatalog::allowed($user,$event)
            &&!empty($s[$channel]['enabled'])&&in_array($destination,$s[$channel]['destinations']??[],true)
            &&$this->preference($user,$event->kind,$channel);
    }
    public function enqueue(object $event,User $user): void {
        if(!$this->ready())return;
        foreach($this->availableChannels($user)as$channel)foreach($this->settings()[$channel]['destinations']as$destination) {
            if(!$this->approved($user,$event,$channel,$destination))continue;
            $hash=hash('sha256',$destination);
            DB::table('notification_outbound')->insertOrIgnore([
                'notification_event_id'=>$event->id,'user_id'=>$user->id,'channel'=>$channel,
                'destination'=>Crypt::encryptString($destination),'destination_hash'=>$hash,
                'delivery_key'=>hash('sha256',$event->event_key.'|'.$user->id.'|'.$channel.'|'.$hash),
                'locale'=>$this->settings()['locale']??'fr','available_at'=>now('UTC'),
                'created_at'=>now('UTC'),'updated_at'=>now('UTC'),
            ]);
        }
    }
    public function pump(int $limit=20): int {
        if(!$this->ready())return 0;
        $limit=max(1,min($limit,100));
        $stale=DB::table('notification_outbound')->where('state','sending')->where('claimed_at','<',now('UTC')->subMinutes(10))
            ->orderBy('id')->limit($limit)->pluck('id');
        foreach($stale as$id)DB::transaction(function()use($id){
            $d=DB::table('notification_outbound')->where('id',$id)->lockForUpdate()->first();
            if($d&&$d->state==='sending'&&$d->claimed_at<now('UTC')->subMinutes(10)->format('Y-m-d H:i:s'))
                $this->state($id,'ambiguous','delivery_confirmation_missing');
        },3);
        $ids=DB::table('notification_outbound')->whereIn('state',['pending','retry','unconfigured'])
            ->where('available_at','<=',now('UTC'))->where(fn($q)=>$q->whereNull('queued_at')->orWhere('queued_at','<',now('UTC')->subMinutes(10)))
            ->orderBy('id')->limit($limit)->pluck('id');
        foreach($ids as$id)DB::transaction(function()use($id) {
            $d=DB::table('notification_outbound')->where('id',$id)->lockForUpdate()->first();
            if(!$d||!in_array($d->state,['pending','retry','unconfigured'],true)||($d->queued_at&&$d->queued_at>now('UTC')->subMinutes(10)->format('Y-m-d H:i:s')))return;
            DB::table('notification_outbound')->where('id',$id)->update(['queued_at'=>now('UTC'),'updated_at'=>now('UTC')]);
            DeliverExternalAlert::dispatch($id)->onConnection(config('notifications.connection'))->onQueue(config('notifications.queue'))->afterCommit();
        },3);
        return $ids->count();
    }
    public function deliver(int $id): void {
        $hint=DB::table('notification_outbound')->find($id);if(!$hint)return;
        $claim=DB::transaction(function()use($hint,$id) {
            DB::table('roles')->where('name','Admin')->where('guard_name','web')->sharedLock()->first();
            $event=DB::table('notification_events')->where('id',$hint->notification_event_id)->sharedLock()->first();
            $user=User::whereKey($hint->user_id)->sharedLock()->first();
            $d=DB::table('notification_outbound')->where('id',$id)->lockForUpdate()->first();
            if(!$d||!in_array($d->state,['pending','retry','unconfigured'],true)||$d->available_at>now('UTC')->format('Y-m-d H:i:s'))return null;
            try{$destination=Crypt::decryptString($d->destination);}catch(\Throwable){$this->state($id,'failed','destination_unreadable');return null;}
            if(!$event||!$user||!$this->approved($user,$event,$d->channel,$destination)||!app(NotificationRelevance::class)->current($event)){
                $this->state($id,'cancelled','authorization_or_source_changed');return null;
            }
            if(!$this->channelReady($d->channel,$d->locale)){
                $this->state($id,'unconfigured','transport_unconfigured',['available_at'=>now('UTC')->addMinutes(15),'queued_at'=>null]);return null;
            }
            $this->state($id,'sending',null,['attempts'=>$d->attempts+1,'claimed_at'=>now('UTC')]);
            return compact('d','event','user','destination');
        },3);
        if(!$claim)return;
        extract($claim);$accepted=false;
        try{
            if($d->channel==='email'){
                $mailer=config('reporting.mailer');config(['mail.mailers.'.$mailer.'.timeout'=>20]);
                Mail::mailer($mailer)->to($destination)->send(new OperationalAlertMail($event,$user,$d->locale));
                $accepted=true;$this->state($id,'accepted',null,['accepted_at'=>now('UTC')]); // Acceptance is not recipient delivery.
            }else{
                $c=config('notifications.twilio');
                $body=['To'=>($d->channel==='whatsapp'?'whatsapp:':'').$destination,'From'=>($d->channel==='whatsapp'?'whatsapp:':'').$c[$d->channel.'_from']];
                $title=trans('notifications.types.'.$event->kind,[],$d->locale);
                $link=NotificationCatalog::link($event,$user)??url('/admin/notification-preferences');
                if($d->channel==='whatsapp'){
                    $body['ContentSid']=$c['templates'][$d->locale];
                    $body['ContentVariables']=json_encode(['1'=>$title,'2'=>(string)($event->point_of_sale_id??'global'),'3'=>(string)($event->source_id??'-'),'4'=>$link],JSON_THROW_ON_ERROR);
                }else{$body['Body']='QPOS: '.$title.' · #'.($event->point_of_sale_id??'global').' · #'.($event->source_id??'-').' '.$link;}
                $response=Http::withBasicAuth($c['account_sid'],$c['auth_token'])->asForm()->connectTimeout(5)->timeout(20)
                    ->withOptions(['allow_redirects'=>false])->post('https://api.twilio.com/2010-04-01/Accounts/'.$c['account_sid'].'/Messages.json',$body);
                if($response->status()===429){$this->retry($d,'provider_throttled');return;}
                if($response->clientError()){$this->state($id,'failed','provider_rejected');return;}
                if(!$response->successful()){$this->state($id,'ambiguous','provider_confirmation_missing');return;}
                $accepted=true;$sid=$response->json('sid');
                if(!is_string($sid)||!preg_match('/^(SM|MM)[0-9a-fA-F]{32}$/',$sid)){$this->state($id,'ambiguous','provider_confirmation_missing');return;}
                $this->state($id,'accepted',null,['provider_id'=>$sid,'accepted_at'=>now('UTC'),'available_at'=>now('UTC')->addMinutes(5)]);
            }
        }catch(\Throwable$error){
            $uncertain=$accepted||$error instanceof ConnectionException;
            if($error instanceof \Symfony\Component\Mailer\Exception\TransportExceptionInterface)$uncertain=$uncertain||str_contains(method_exists($error,'getDebug')?$error->getDebug():'','DATA');
            if($uncertain)$this->state($id,'ambiguous','delivery_confirmation_missing');else$this->retry($d,'transport_or_generation_failure');
        }
    }
    public function refreshAccepted(int $limit=20): int {
        if(!$this->ready())return 0;
        $rows=DB::table('notification_outbound')->where('state','accepted')->whereIn('channel',['sms','whatsapp'])
            ->where('available_at','<=',now('UTC'))->orderBy('id')->limit(max(1,min($limit,100)))->get();
        foreach($rows as$d){
            if(!$this->channelReady($d->channel,$d->locale))continue;
            $event=DB::table('notification_events')->find($d->notification_event_id);$user=User::find($d->user_id);
            try{
                if(!$event||!$user||!$this->approved($user,$event,$d->channel,Crypt::decryptString($d->destination)))continue;
                if(!preg_match('/^(SM|MM)[0-9a-fA-F]{32}$/',$d->provider_id??''))continue;
                $c=config('notifications.twilio');
                $response=Http::withBasicAuth($c['account_sid'],$c['auth_token'])->connectTimeout(5)->timeout(15)->withOptions(['allow_redirects'=>false])
                    ->get('https://api.twilio.com/2010-04-01/Accounts/'.$c['account_sid'].'/Messages/'.$d->provider_id.'.json');
                $status=$response->successful()?$response->json('status'):null;
                if(in_array($status,['delivered','read'],true))$this->statusState($d->id,'delivered',null,['delivered_at'=>now('UTC')]);
                elseif(in_array($status,['failed','undelivered','canceled'],true))$this->statusState($d->id,'failed','provider_delivery_failed');
                else$this->statusState($d->id,'accepted',$response->successful()?null:'provider_status_unavailable',['available_at'=>now('UTC')->addMinutes(5)]);
            }catch(\Throwable){$this->statusState($d->id,'accepted','provider_status_unavailable',['available_at'=>now('UTC')->addMinutes(5)]);}
        }
        return $rows->count();
    }
    private function statusState(int $id,string $state,?string $error,array $extra=[]): void {
        DB::table('notification_outbound')->where('id',$id)->where('state','accepted')->update($extra+['state'=>$state,'last_error'=>$error,'updated_at'=>now('UTC')]);
    }
    private function retry(object $d,string $code): void {
        $attempt=$d->attempts+1;
        $this->state($d->id,$attempt>=5?'failed':'retry',$code,['queued_at'=>null,'available_at'=>now('UTC')->addSeconds(min(3600,60*(2**min($attempt,6))))]);
    }
    private function state(int $id,string $state,?string $error,array $extra=[]): void {
        DB::transaction(function()use($id,$state,$error,$extra){
            $d=DB::table('notification_outbound')->where('id',$id)->lockForUpdate()->first();
            if(!$d)return;
            DB::table('notification_outbound')->where('id',$id)->update($extra+['state'=>$state,'last_error'=>$error,'updated_at'=>now('UTC')]);
            if($d->channel==='email'&&in_array($state,['failed','ambiguous'],true)){
                $attempt=(int)($extra['attempts']??$d->attempts);
                app(OperationalNotifications::class)->record('email_failed','external:'.$id.':'.$attempt.':'.$state,null,$id,
                    ['channel'=>'email','state'=>$state,'attempts'=>$attempt]);
            }
        },3);
    }
}
