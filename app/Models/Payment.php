<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Payments are immutable; record a linked reversal.'));
        static::deleting(fn () => throw new \LogicException('Payments are immutable; record a linked reversal.'));
    }
    protected $guarded = [];
    protected $casts = ['source_amount' => 'decimal:6', 'received_amount' => 'decimal:6', 'change_amount' => 'decimal:6', 'net_amount' => 'decimal:6', 'occurred_at' => \App\Casts\StockDateTime::class];
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function allocations() { return $this->hasMany(PaymentAllocation::class); }
    public function reversal() { return $this->hasOne(self::class, 'reversal_of_id'); }
}
