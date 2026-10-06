<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Payment allocations are immutable.'));
        static::deleting(fn () => throw new \LogicException('Payment allocations are immutable.'));
    }
    protected $guarded = [];
    protected $casts = ['amount' => 'decimal:6'];
    public function payment() { return $this->belongsTo(Payment::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
