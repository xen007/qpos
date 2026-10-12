<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

final class UserAdministration
{
    public static function lock(): void
    {
        Role::where('name','Admin')->where('guard_name','web')->lockForUpdate()->first();
    }

    public static function query(User $actor)
    {
        $query = User::query();
        if (!RoleAssignment::globalManager($actor)) {
            $shops = $actor->pointOfSales()->where('points_of_sale.is_active',true)->wherePivot('is_active',true)->pluck('points_of_sale.id');
            $query->whereDoesntHave('roles',fn($q)=>$q->where('name','Admin'))
                ->whereHas('pointOfSales',fn($q)=>$q->whereIn('points_of_sale.id',$shops)->where('point_of_sale_user.is_active',true));
        }
        return $query;
    }

    public static function protectLast(User $target, bool $retainsAdmin): void
    {
        // All admin-removal/suspension/deletion paths acquire this same lock first.
        $role=Role::where('name','Admin')->where('guard_name','web')->lockForUpdate()->first();
        if (!$role || !$target->hasRole('Admin') || $retainsAdmin || $target->is_suspended) return;
        $remaining=User::role('Admin')->where('is_suspended',false)->where('id','<>',$target->id)->get();
        if (!$remaining->contains(fn($user)=>RoleAssignment::globalManager($user))) {
            throw ValidationException::withMessages(['roles'=>__('administration.last_admin')]);
        }
    }

    public static function auditShop(User $actor, User $target): ?int
    {
        if (RoleAssignment::globalManager($actor)) return null;
        $shops=$actor->pointOfSales()->where('points_of_sale.is_active',true)->wherePivot('is_active',true)->pluck('points_of_sale.id');
        return $target->pointOfSales()->whereIn('points_of_sale.id',$shops)->wherePivot('is_active',true)->orderBy('points_of_sale.id')->value('points_of_sale.id');
    }
}
