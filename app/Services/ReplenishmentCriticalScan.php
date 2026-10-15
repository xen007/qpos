<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class ReplenishmentCriticalScan
{
    public function run(int $limit = 200): array
    {
        if (!app(ReplenishmentPlanner::class)->ready() || !app(OperationalNotifications::class)->ready()) return ['unavailable'=>true];
        $counts=['products'=>0,'events'=>0,'unavailable_actors'=>0];
        $settingCursor=(int)DB::table('reporting_runtime')->where('key','replenishment_critical_shop_cursor')->value('value');
        $settings=DB::table('replenishment_settings')->where('critical_immediate',true)->whereNotNull('responsible_user_id')->where('id','>',$settingCursor)->orderBy('id')->limit(20)->get();
        DB::table('reporting_runtime')->updateOrInsert(['key'=>'replenishment_critical_shop_cursor'],['value'=>$settings->count()===20?(string)$settings->last()->id:'0']);
        foreach ($settings as $setting) {
            $shop=(int)$setting->point_of_sale_id;
            $key='replenishment_critical_cursor_'.$shop;
            $cursor=(int)DB::table('reporting_runtime')->where('key',$key)->value('value');
            $ids=app(PurchaseOrderSuggestionService::class)->products()->where('products.id','>',$cursor)->orderBy('products.id')
                ->limit(max(1,min(200,$limit)))->pluck('products.id')->all();
            try {$rows=app(PurchaseOrderSuggestionService::class)->getSuggestions((int)$setting->responsible_user_id,$shop,$ids,['include_transfers'=>false]);}
            catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|\Illuminate\Database\Eloquent\ModelNotFoundException $error) {$counts['unavailable_actors']++;continue;}
            foreach ($rows as $row) DB::transaction(function () use ($row,$setting,$shop,&$counts) {
                $stateKey='purchase-risk:'.$shop.':'.$row['product_id'];
                DB::table('notification_states')->insertOrIgnore(['key'=>$stateKey,'value'=>'0','generation'=>0,'created_at'=>now('UTC'),'updated_at'=>now('UTC')]);
                $state=DB::table('notification_states')->where('key',$stateKey)->lockForUpdate()->first();
                $active=$row['critical']&&$row['policy']['alerts_enabled'];
                $value=(int)$active.'|'.$setting->version;
                if ($state->value===$value) return;
                DB::table('notification_states')->where('key',$stateKey)->update(['value'=>$value,'generation'=>$state->generation+1,'updated_at'=>now('UTC')]);
                if (!$active) return;
                $eventKey='purchase-risk:'.$shop.':'.$row['product_id'].':'.now('Africa/Douala')->toDateString();
                $hash=hash('sha256','purchase_order_critical|'.$eventKey.'|'.$shop);
                if (DB::table('notification_events')->where('event_key',$hash)->exists()) return;
                app(OperationalNotifications::class)->record('purchase_order_critical',$eventKey,$shop,(int)$row['product_id'],
                    ['daily'=>$row['daily'],'delay_days'=>$row['delay_days']]);
                $counts['events']++;
            },3);
            $counts['products']+=count($ids);
            DB::table('reporting_runtime')->updateOrInsert(['key'=>$key],['value'=>count($ids)===max(1,min(200,$limit))?(string)max($ids):'0']);
        }
        return $counts;
    }
}
