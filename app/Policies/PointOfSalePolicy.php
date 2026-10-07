<?php
namespace App\Policies;

use App\Models\PointOfSale;
use App\Models\User;

class PointOfSalePolicy
{
    private function eligible(User $user): bool
    {
        return !$user->is_suspended && $user->getRoleNames()->isNotEmpty();
    }
    private function managementScope(User $user, PointOfSale $shop): bool
    {
        if (!config('system.multi_shop_enabled', true)) return (int)$shop->id === 1;
        if (PointOfSale::active()->count() === 1) return $shop->is_active;
        return $user->can('point_of_sale_manage_all') || $shop->users()
            ->whereKey($user->id)->where('point_of_sale_user.is_active', true)->exists();
    }
    public function viewAny(User $user): bool
    {
        return config('system.multi_shop_enabled', true) && $this->eligible($user) && $user->can('point_of_sale_view');
    }
    public function inspect(User $user, PointOfSale $shop): bool
    {
        return $this->viewAny($user) && $this->managementScope($user, $shop);
    }
    public function view(User $user, PointOfSale $shop): bool
    {
        return $this->eligible($user) && $user->can('point_of_sale_access')
            && $shop->is_active && ( !config('system.multi_shop_enabled', true)
                ? (int)$shop->id === 1
                : (PointOfSale::active()->count() === 1 || $user->hasActivePointOfSale($shop->id)) );
    }
    public function create(User $user): bool
    {
        return config('system.multi_shop_enabled', true) && $this->eligible($user) && $user->can('point_of_sale_create');
    }
    public function update(User $user, PointOfSale $shop): bool
    {
        return $this->eligible($user) && $user->can('point_of_sale_update') && $this->managementScope($user, $shop);
    }
    public function delete(User $user, PointOfSale $shop): bool
    {
        return config('system.multi_shop_enabled', true) && $this->eligible($user) && $user->can('point_of_sale_delete') && $this->managementScope($user, $shop);
    }
    public function assign(User $user, PointOfSale $shop): bool
    {
        return $this->eligible($user) && $user->can('point_of_sale_assign') && $this->managementScope($user, $shop);
    }
    public function assignNew(User $user): bool
    {
        return $this->create($user) && $user->can('point_of_sale_assign');
    }
}
