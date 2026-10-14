<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Services\{ExternalAlertService,SystemSettings};
use App\Support\RoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class NotificationSettingsController extends Controller
{
    private function authorizeSettings(Request $r): void{
        $u=$r->user()?->fresh();abort_unless($u&&RoleAssignment::globalManager($u)&&$u->can('system_monitor'),403);
    }
    public function index(Request $r){
        $this->authorizeSettings($r);$external=app(ExternalAlertService::class);$settings=$external->settings();
        $deliveries=$external->ready()?DB::table('notification_outbound as d')->join('notification_events as e','e.id','=','d.notification_event_id')
            ->orderByDesc('d.id')->select(['d.id','d.channel','d.state','d.attempts','d.last_error','d.created_at','e.kind','e.point_of_sale_id'])->paginate(20):null;
        return view('backend.notifications.settings',compact('external','settings','deliveries'));
    }
    public function update(Request $r,SystemSettings $settings){
        $this->authorizeSettings($r);
        $v=$r->validate([
            'critical_threshold'=>'nullable|integer|min:1|max:999999999999','low_threshold'=>'required|integer|min:1|max:1000000',
            'external_present'=>'nullable|boolean','external_locale'=>'nullable|in:fr,en','external_kinds'=>'nullable|array|max:5','external_kinds.*'=>'string|distinct',
            'email_enabled'=>'nullable|boolean','sms_enabled'=>'nullable|boolean','whatsapp_enabled'=>'nullable|boolean',
            'email_destinations'=>'nullable|string|max:5000','sms_destinations'=>'nullable|string|max:1000','whatsapp_destinations'=>'nullable|string|max:1000',
            'external_consent'=>'nullable|accepted',
        ]);
        $values=['notification_critical_sale_threshold'=>isset($v['critical_threshold'])?(string)$v['critical_threshold']:null,'notification_low_stock_threshold'=>(string)$v['low_threshold']];
        if($r->boolean('external_present')){
            $kinds=$v['external_kinds']??[];
            if(array_diff($kinds,ExternalAlertService::KINDS))throw ValidationException::withMessages(['external_kinds'=>__('external_alerts.invalid')]);
            $routes=['owner_id'=>$r->user()->id,'locale'=>$v['external_locale']??'fr','kinds'=>$kinds];
            $active=false;
            foreach(ExternalAlertService::CHANNELS as$c){
                $list=preg_split('/[\s,;]+/',trim($v[$c.'_destinations']??''),-1,PREG_SPLIT_NO_EMPTY);
                if($c==='email')$list=array_map('strtolower',$list);
                $list=array_values(array_unique($list));$enabled=$r->boolean($c.'_enabled');
                if(count($list)>20||($enabled&&(!$list||!$kinds)))throw ValidationException::withMessages([$c.'_destinations'=>__('external_alerts.invalid')]);
                foreach($list as$destination){
                    $valid=$c==='email'?filter_var($destination,FILTER_VALIDATE_EMAIL):preg_match('/^\+[1-9][0-9]{6,14}$/',$destination);
                    if(!$valid)throw ValidationException::withMessages([$c.'_destinations'=>__('external_alerts.invalid')]);
                }
                $routes[$c]=['enabled'=>$enabled,'destinations'=>$list];$active=$active||$enabled;
            }
            if($active&&!$r->boolean('external_consent'))throw ValidationException::withMessages(['external_consent'=>__('external_alerts.consent_required')]);
            $routes['consent_at']=$active?now('UTC')->toIso8601String():null;
            $values['external_alerts']=$routes;
        }
        $settings->update($values);
        return back()->with('success',__('notifications.saved'));
    }
}
