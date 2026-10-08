<?php

namespace App\Casts;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PosCart;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SaleDateTime implements CastsAttributes
{
    private function native(Model $model, array $attributes): bool
    {
        if ($model instanceof CashSession || $model instanceof CashMovement) {
            return true;
        }
        if ($model instanceof PaymentAllocation) {
            return ! empty($attributes['order_id']);
        }
        if ($model instanceof PosCart) {
            return ! empty($attributes['cart_id']);
        }

        return $model instanceof Order ? ($attributes['currency_code'] ?? null) === 'XAF' : ! empty($attributes['receipt_snapshot']);
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }
        $zone = $this->native($model, $attributes) ? 'UTC' : ($model instanceof Payment && $key === 'occurred_at' ? 'Africa/Douala' : config('app.timezone'));

        return CarbonImmutable::parse($value, $zone);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }
        $zone = $this->native($model, $attributes) ? 'UTC' : ($model instanceof Payment && $key === 'occurred_at' ? 'Africa/Douala' : config('app.timezone'));
        $date = $value instanceof DateTimeInterface ? CarbonImmutable::instance($value) : CarbonImmutable::parse($value, $zone);

        return $date->setTimezone($zone)->format('Y-m-d H:i:s');
    }
}
