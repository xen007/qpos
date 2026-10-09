<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class SaleFormat
{
    public static function moneyDisplay(mixed $value): string
    {
        $result = (string) BigDecimal::of((string) ($value ?? '0'))->toScale(2, RoundingMode::HalfUp);
        return app()->getLocale() === 'fr' ? str_replace('.', ',', $result) : $result;
    }

    public static function quantityDisplay(mixed $value): string
    {
        $result = self::decimal($value);
        return app()->getLocale() === 'fr' ? str_replace('.', ',', $result) : $result;
    }

    public static function decimal(mixed $value): string
    {
        return (string) BigDecimal::of((string) ($value ?? '0'))->stripTrailingZeros();
    }

    public static function xaf(mixed $value): string
    {
        return (string) BigDecimal::of((string) ($value ?? '0'))->toScale(0);
    }
}
