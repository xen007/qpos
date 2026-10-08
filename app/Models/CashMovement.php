<?php

namespace App\Models;

use App\Casts\SaleDateTime;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    public function freshTimestamp()
    {
        return now('UTC');
    }

    protected $guarded = [];

    protected $casts = ['created_at' => SaleDateTime::class, 'updated_at' => SaleDateTime::class];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Cash movements are immutable.'));
        static::deleting(fn () => throw new \LogicException('Cash movements are immutable.'));
    }
}
