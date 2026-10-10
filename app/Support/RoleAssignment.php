<?php
namespace App\Support;
use App\Models\User;
use Spatie\Permission\Models\Role;

final class RoleAssignment
{
    public static function globalManager(User $actor): bool { return !$actor->is_suspended && $actor->hasRole('Admin') && $actor->hasAllPermissions(['role_update','point_of_sale_manage_all']); }
    public static function choices(User $actor)
    {
        return Role::with('permissions')->where('guard_name','web')->orderBy('name')->get()->filter(fn($role)=>self::globalManager($actor) || ($role->name!=='Admin' && !$role->permissions->contains('name','point_of_sale_manage_all') && $role->permissions->every(fn($p)=>$actor->can($p->name))));
    }
    public static function resolve(User $actor, array $ids)
    {
        $allowed=self::choices($actor)->whereIn('id',$ids);
        abort_unless(count($ids)===$allowed->count(),403);
        return $allowed;
    }
    public static function protectTarget(User $actor, User $target): void
    {
        abort_unless(self::globalManager($actor) || (!$target->hasRole('Admin') && !$target->can('point_of_sale_manage_all') && $target->getAllPermissions()->every(fn($p)=>$actor->can($p->name))),403);
    }
}
