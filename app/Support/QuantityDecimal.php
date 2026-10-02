<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\RoundingNecessaryException;
use Illuminate\Validation\ValidationException;

final class QuantityDecimal
{
    public const MAX = '99999999999999.999999';

    public static function parse(mixed $value, string $field = 'quantity', bool $positive = false): BigDecimal
    {
        // Les floats ne permettent pas de garantir une saisie decimale exacte.
        if ((!is_string($value) && !is_int($value))
            || !preg_match('/\A(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?\z/D', (string) $value)) {
            throw ValidationException::withMessages([$field => __('Use a decimal number with at most six decimal places.')]);
        }
        $decimal = BigDecimal::of((string) $value);
        if ($decimal->isGreaterThan(self::MAX) || ($positive && $decimal->isLessThanOrEqualTo('0'))) {
            throw ValidationException::withMessages([$field => __('The quantity or factor is outside the supported range.')]);
        }
        return $decimal;
    }

    public static function toBase(mixed $quantity, mixed $factor, bool $allowsFractional): string
    {
        $amount = self::parse($quantity, 'quantity', true);
        $multiplier = self::parse($factor, 'factor', true);
        try {
            $result = $amount->multipliedBy($multiplier)->toScale(6);
            if (!$allowsFractional) {
                $amount->toScale(0);
                $result->toScale(0);
            }
        } catch (RoundingNecessaryException) {
            throw ValidationException::withMessages(['quantity' => $allowsFractional
                ? __('The conversion exceeds six decimal places; no rounding is allowed.')
                : __('This product requires whole quantities, including in its base unit.')]);
        }
        if ($result->isGreaterThan(self::MAX)) {
            throw ValidationException::withMessages(['quantity' => __('The quantity or factor is outside the supported range.')]);
        }
        return (string) $result;
    }
}
