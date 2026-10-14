<?php

namespace App\Support;

use App\Models\{PointOfSale, User};
use App\Services\AdministrationAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

final class UserAccessSettings
{
    // Shared catalogue writes and system administration are deliberately not delegable locally.
    public const DELEGABLE = [
        'point_of_sale_access','point_of_sale_view','product_view','customer_view','customer_create','customer_update','customer_sales',
        'supplier_view','purchase_view','purchase_create','purchase_update','purchase_receive','purchase_pay','purchase_cancel',
        'stock_policy_manage','purchase_order_view','purchase_order_create','purchase_order_update','purchase_order_delete','purchase_order_approve','purchase_order_send','purchase_order_receive','purchase_order_cancel','purchase_order_surplus','purchase_order_export','purchase_suggestion_manage',
        'stock_view','stock_transfer_dispatch','stock_transfer_receive','stock_inventory',
        'sale_create','sale_view','sale_collect','sale_discount','sale_return','customer_credit_manage',
        'pending_sale_prepare','pending_sale_collect','cash_session_manage','cash_movement_create',
        'expense_view','expense_manage','price_labels','dashboard_view','reports_summary','reports_sales','reports_inventory',
        'daily_summary_view','daily_summary_receive','audit_view',
    ];

    public static function label(string $role): string
    {
        return match ($role) {
            'Admin' => __('phase7.global_admin'), 'shop_admin' => __('phase7.shop_admin'), default => $role,
        };
    }

    public static function shopAdminCapabilities(): array
    {
        return array_merge(self::DELEGABLE,['user_view','user_create','user_update','user_suspend','role_view','permission_view','user_permissions_manage']);
    }

    public static function choices(User $actor)
    {
        return Permission::where('guard_name','web')->whereIn('name',self::DELEGABLE)->orderBy('name')->get()
            ->filter(fn($permission)=>$actor->can($permission->name));
    }

    public static function form(User $actor, ?User $target): array
    {
        $allowed = !$actor->is_suspended && $actor->can('user_permissions_manage');
        return [
            'allowed'=>$allowed, 'permissions'=>$allowed ? self::choices($actor) : collect(),
            'direct'=>$target?->getDirectPermissions()->pluck('name')->all() ?? [],
            'inherited'=>$target?->getPermissionsViaRoles()->pluck('name')->sort()->values()->all() ?? [],
            'shops'=>$allowed ? PointOfSale::active()->when(!RoleAssignment::globalManager($actor),fn($q)=>$q->whereHas('users',fn($u)=>$u->whereKey($actor->id)->where('point_of_sale_user.is_active',true)))->when($target,fn($q)=>$q->whereIn('id',PointOfSale::accessibleBy($target)->pluck('points_of_sale.id')))->orderBy('name')->get() : collect(),
        ];
    }

    public static function apply(Request $request, User $target, bool $created = false): void
    {
        if (!$request->has('access_payload')) return;
        if (DB::transactionLevel() < 1) throw new \LogicException('Access settings require the administrative transaction.');
        $actor = $request->user()->fresh();
        abort_unless($actor && !$actor->is_suspended && $actor->can('user_permissions_manage') && $actor->can($created?'user_create':'user_update'),403);
        RoleAssignment::protectTarget($actor,$target);
        $data=$request->validate([
            'access_payload'=>'required|in:1', 'direct_permissions'=>'nullable|array|max:100',
            'direct_permissions.*'=>'required|string|max:100|distinct',
            'fixed_point_of_sale_id'=>'nullable|integer', 'access_reason'=>'nullable|string|max:500',
        ]);
        $choices=self::choices($actor)->pluck('name')->all();
        $selected=$data['direct_permissions']??[];
        abort_unless(count(array_diff($selected,$choices))===0,403);
        $current=$target->getDirectPermissions()->pluck('name')->all();
        // Do not erase old direct rights outside the editable allowlist.
        $next=array_values(array_unique(array_merge(array_diff($current,$choices),$selected)));
        sort($current);sort($next);
        $fixed=isset($data['fixed_point_of_sale_id'])?(int)$data['fixed_point_of_sale_id']:null;
        $oldFixed=$target->fixed_point_of_sale_id ? (int)$target->fixed_point_of_sale_id : null;
        if($fixed) {
            $shop=PointOfSale::active()->find($fixed);
            abort_unless($shop && PointOfSale::accessibleBy($actor)->whereKey($fixed)->exists(),403);
            if($created && RoleAssignment::globalManager($actor)) $target->pointOfSales()->syncWithoutDetaching([$fixed=>['is_active'=>true]]);
            abort_unless(PointOfSale::accessibleBy($target)->whereKey($fixed)->exists(),403);
            if($fixed!==$oldFixed) {
                $open=DB::table('cash_sessions')->where('user_id',$target->id)->where('state','open')->where('point_of_sale_id','<>',$fixed)->exists();
                $pending=Schema::hasTable('pending_sales') && DB::table('pending_sales')->where(fn($q)=>$q->where('seller_user_id',$target->id)->orWhere('cashier_user_id',$target->id))->whereIn('state',['pending','taken'])->where('point_of_sale_id','<>',$fixed)->exists();
                if($open||$pending)throw ValidationException::withMessages(['fixed_point_of_sale_id'=>__('phase7.finish_operations')]);
            }
        }
        if($current===$next && $fixed===$oldFixed)return;
        $reason=trim($data['access_reason']??'');
        if($reason==='')throw ValidationException::withMessages(['access_reason'=>__('phase7.reason_required')]);
        $target->syncPermissions($next);
        $target->forceFill(['fixed_point_of_sale_id'=>$fixed])->save();
        app(AdministrationAudit::class)->record('user.access_updated','user',$target->id,
            ['permissions'=>$current,'fixed_point_of_sale_id'=>$oldFixed],
            ['permissions'=>$next,'fixed_point_of_sale_id'=>$fixed],UserAdministration::auditShop($actor,$target),$reason);
    }
}
