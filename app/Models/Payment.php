<?php

namespace App\Models;

use App\Casts\SaleDateTime;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Payments are immutable; record a linked reversal.'));
        static::deleting(fn () => throw new \LogicException('Payments are immutable; record a linked reversal.'));
    }

    public function freshTimestamp()
    {
        return ! empty($this->receipt_snapshot) ? now('UTC') : parent::freshTimestamp();
    }

    protected $guarded = [];

    protected $casts = ['created_at' => SaleDateTime::class, 'updated_at' => SaleDateTime::class, 'receipt_snapshot' => 'array', 'source_amount' => 'decimal:6', 'received_amount' => 'decimal:6', 'change_amount' => 'decimal:6', 'net_amount' => 'decimal:6', 'occurred_at' => SaleDateTime::class];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }
}
