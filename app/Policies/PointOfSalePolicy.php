<?php

namespace App\Policies;

use App\Models\PointOfSale;
use App\Models\User;

class PointOfSalePolicy
{
    public function viewAny(User $user): bool
    {
        return !$user->is_suspended
            && $user->getRoleNames()->isNotEmpty()
            && $user->can('point_of_sale_view')
            && $user->pointOfSales()->where('points_of_sale.is_active', true)
                ->where('point_of_sale_user.is_active', true)->exists();
    }

    public function view(User $user, PointOfSale $pointOfSale): bool
    {
        return !$user->is_suspended
            && $user->getRoleNames()->isNotEmpty()
            && $user->can('point_of_sale_access')
            && $pointOfSale->is_active
            && $user->hasActivePointOfSale($pointOfSale->getKey());
    }
}
