<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointOfSale extends Model
{
    protected $table = 'points_of_sale';

    protected $fillable = ['code', 'name', 'address', 'is_active'];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function batchStocks(): HasMany
    {
        return $this->hasMany(BatchStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where('points_of_sale.is_active', true)
            ->whereHas('users', function (Builder $users) use ($user) {
                $users->whereKey($user->getKey())
                    ->where('point_of_sale_user.is_active', true);
            });
    }
}
