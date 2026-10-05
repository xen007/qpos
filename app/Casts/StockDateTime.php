<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** New stock dates are stored in Douala; legacy application dates stay intact. */
final class StockDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value, 'Africa/Douala');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) { return null; }
        $date = $value instanceof DateTimeInterface ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value, 'Africa/Douala');
        return $date->setTimezone('Africa/Douala')->format('Y-m-d H:i:s');
    }
}
