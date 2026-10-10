<?php
namespace App\Support;

use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class PointOfSaleContext
{
    public static function ready(): bool
    {
        static $ready = null;
        return $ready ??= Schema::hasColumn('users', 'preferred_point_of_sale_id')
            && Schema::hasTable('points_of_sale') && Schema::hasTable('point_of_sale_user');
    }
    public static function manageableBy(User $user)
    {
        if ($user->is_suspended || $user->getRoleNames()->isEmpty()) return PointOfSale::query()->whereRaw('1 = 0');
        return PointOfSale::query()->when(!$user->can('point_of_sale_manage_all'), function ($query) use ($user) {
            $query->whereHas('users', fn ($q) => $q->whereKey($user->id)->where('point_of_sale_user.is_active', true));
        });
    }
}
