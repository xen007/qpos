<?php

namespace App\Support;

use Brick\Math\BigDecimal;

final class SaleFormat
{
    public static function decimal(mixed $value): string
    {
        return (string) BigDecimal::of((string) ($value ?? '0'))->stripTrailingZeros();
    }

    public static function xaf(mixed $value): string
    {
        return (string) BigDecimal::of((string) ($value ?? '0'))->toScale(0);
    }
}
