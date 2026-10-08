<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderProduct extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['pricing_snapshot' => 'array', 'quantity' => 'decimal:6'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockAllocations(): HasMany
    {
        return $this->hasMany(OrderStockAllocation::class);
    }

    protected $appends = ['discounted_price'];

    public function getDiscountedPriceAttribute()
    {
        return BigDecimal::of($this->quantity)->isZero() ? '0' : (string) BigDecimal::of($this->total)->dividedBy((string) $this->quantity, 6, RoundingMode::HalfUp);
    }
}
