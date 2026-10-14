<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SystemSettings
{
    public const ALLOWED = [
        'is_live','close_msg','site_url','site_name','site_logo','app_name','favicon_icon','favicon_icon_apple',
        'newsletter_subscribe','notify_email_address','notify_messages_status','notify_comments_status',
        'facebook_link','twitter_link','instagram_link','youtube_link','linkedin_link','pinterest_link','whatsapp_link','snapchat_link','tumblr_link',
        'meta_keywords','meta_description','meta_title','meta_image','contact_address','contact_phone','contact_fax','contact_mobile','contact_email','working_hour',
        'custom_css','note_to_customer_invoice','is_show_logo_invoice','is_show_site_invoice','is_show_phone_invoice','is_show_email_invoice','is_show_address_invoice','is_show_customer_invoice','is_show_note_invoice','receiptMaxwidth',
        'site_palette','multi_shop_enabled','default_expiry_months','auto_generate_lots','default_lot_prefix','allow_sale_without_lot',
        'daily_summary_email_enabled','daily_summary_email_recipients',
        'notification_critical_sale_threshold','notification_low_stock_threshold','external_alerts',
    ];
    public const AUDITABLE = ['is_live','site_name','site_url','site_palette','multi_shop_enabled','default_expiry_months','auto_generate_lots','default_lot_prefix','allow_sale_without_lot','daily_summary_email_enabled','receiptMaxwidth','notification_critical_sale_threshold','notification_low_stock_threshold','external_alerts'];

    public function load(): void
    {
        if (!Schema::hasTable('system_settings')) return;
        foreach (DB::table('system_settings')->whereIn('key',self::ALLOWED)->get() as $setting) {
            config(['system.'.$setting->key=>json_decode($setting->value,true,512,JSON_THROW_ON_ERROR)]);
        }
    }

    public function update(array $values): void
    {
        foreach ($values as $key=>$value) {
            if (!in_array($key,self::ALLOWED,true) || (!is_scalar($value) && $value!==null && !is_array($value))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['settings'=>__('administration.invalid_setting')]);
            }
        }
        abort_unless(Schema::hasTable('system_settings'),503,__('administration.migration_required'));
        $before = [];
        DB::transaction(function () use ($values,&$before) {
            \App\Support\UserAdministration::lock();
            foreach ($values as $key=>$value) {
                $current=DB::table('system_settings')->where('key',$key)->lockForUpdate()->first();
                $before[$key]=$current?json_decode($current->value,true,512,JSON_THROW_ON_ERROR):config('system.'.$key);
                DB::table('system_settings')->updateOrInsert(['key'=>$key], ['value'=>json_encode($value,JSON_THROW_ON_ERROR),'updated_by'=>auth()->id(),'updated_at'=>now('UTC')]);
            }
            app(AdministrationAudit::class)->record('settings.updated','system',null,['settings'=>$before],['settings'=>$values,'changed_keys'=>array_keys($values)]);
        });
        foreach ($values as $key=>$value) config(['system.'.$key=>$value]);
    }
}
