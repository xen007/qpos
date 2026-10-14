<?php
namespace App\Support;

use App\Models\{User,PointOfSale};

final class NotificationCatalog
{
    public const TYPES=[
        'sale_critical'=>['reports_sales','critical'], 'stock_low'=>['stock_view','warning'],
        'stock_out'=>['stock_view','critical'], 'expiry'=>['stock_view','warning'],
        'debt_overdue'=>['sale_collect','warning'], 'cart_pending'=>['pending_sale_collect','info'],
        'cart_completed'=>['pending_sale_prepare','info'], 'summary_sent'=>['daily_summary_view','info'],
        'purchase_created'=>['purchase_view','info'], 'cash_variance'=>['cash_session_manage','warning'],
        'session_orphan'=>['cash_session_supervise','warning'], 'backup_failed'=>['system_monitor','critical'],
        'email_failed'=>['system_monitor','critical'], 'user_created'=>['user_view','info'],
        'role_changed'=>['role_view','warning'],
    ];

    public static function allowed(User $user,object $event): bool
    {
        if($user->is_suspended||$user->getRoleNames()->isEmpty()||!isset(self::TYPES[$event->kind]))return false;
        $permitted=$user->can(self::TYPES[$event->kind][0])||($event->kind==='stock_low'&&$user->hasAllPermissions(['pending_sale_prepare','sale_create']));
        if(!$permitted)return false;
        if($event->point_of_sale_id){
            if(!$user->can('point_of_sale_access')||!PointOfSale::accessibleBy($user)->whereKey($event->point_of_sale_id)->exists())return false;
        }elseif(!RoleAssignment::globalManager($user))return false;
        if($event->kind==='cart_pending'&&!$user->hasAllPermissions(['sale_create','cash_session_manage']))return false;
        if($event->kind==='stock_low'&&!$user->can('stock_view')&&!$user->hasAllPermissions(['pending_sale_prepare','sale_create']))return false;
        $operational=$event->kind==='cart_pending'||($event->kind==='stock_low'&&!$user->can('stock_view'));
        if($operational&&$user->fixed_point_of_sale_id&&(int)$user->fixed_point_of_sale_id!==(int)$event->point_of_sale_id)return false;
        if($event->kind==='cart_completed'&&(int)$event->target_user_id!==$user->id)return false;
        if($event->kind==='cash_variance'&&(int)$event->target_user_id!==$user->id&&!$user->can('cash_session_supervise')&&!($user->hasRole('shop_admin')&&$user->can('reports_summary')))return false;
        if(in_array($event->kind,['backup_failed','email_failed','session_orphan'],true)&&!RoleAssignment::globalManager($user))return false;
        if($event->kind==='summary_sent'&&!$user->hasAllPermissions(['reports_summary','reports_sales','reports_inventory']))return false;
        return true;
    }

    public static function link(object $event,?User $user=null): ?string
    {
        $shop=$event->point_of_sale_id;
        $path=match($event->kind){
            'sale_critical'=>'/admin/reporting/sales', 'debt_overdue'=>'/admin/orders/'.$event->source_id,
            'cart_pending','cart_completed'=>'/admin/pending-sales',
            'summary_sent'=>'/admin/reporting/summaries/'.$event->source_id,
            'purchase_created'=>'/admin/purchase/'.$event->source_id,
            'cash_variance'=>'/admin/cash', 'session_orphan'=>'/admin/cash-supervision',
            'stock_low'=>$user&&!$user->can('stock_view')?'/admin/cart':'/admin/stock/expiry-alerts', 'stock_out','expiry'=>'/admin/stock/expiry-alerts',
            'backup_failed','email_failed'=>'/admin/system-status',
            'user_created'=>'/admin/users', 'role_changed'=>'/admin/settings/website/roles', default=>null,
        };
        $param=$event->kind==='sale_critical'?'shop_id':'operation_point_of_sale_id';
        return $path===null?null:url($path).($shop?'?'.$param.'='.$shop:'');
    }
}
