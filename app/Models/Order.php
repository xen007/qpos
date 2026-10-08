<?php

namespace App\Models;

use App\Casts\SaleDateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function freshTimestamp()
    {
        return $this->currency_code === 'XAF' && $this->sale_state !== 'legacy' ? now('UTC') : parent::freshTimestamp();
    }

    protected $guarded = [];

    protected $casts = ['created_at' => SaleDateTime::class, 'updated_at' => SaleDateTime::class, 'checkout_snapshot' => 'array', 'total' => 'decimal:6', 'paid' => 'decimal:6', 'due' => 'decimal:6', 'credit_used' => 'decimal:6', 'corrected_total' => 'decimal:6'];

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    protected $appends = ['total_item'];

    public function products()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function transactions()
    {
        return $this->hasMany(OrderTransaction::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getTotalItemAttribute()
    {
        if (array_key_exists('item_quantity_sum', $this->attributes)) {
            return $this->attributes['item_quantity_sum'];
        }

        return $this->products()->sum('quantity');
    }
}
