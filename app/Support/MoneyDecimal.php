<?php
namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

final class MoneyDecimal
{
    public static function parse(mixed $value, string $field = 'amount'): BigDecimal
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/\A\d{1,14}(?:\.\d{1,6})?\z/D', (string) $value)) {
            throw ValidationException::withMessages([$field => __('Use a decimal number with at most six decimal places.')]);
        }
        $amount = BigDecimal::of((string) $value);
        if ($amount->isGreaterThan('99999999999999.999999')) {
            throw ValidationException::withMessages([$field => __('The amount is outside the supported range.')]);
        }
        return $amount->toScale(6);
    }

    public static function rounded(BigDecimal $value): BigDecimal
    {
        $result = $value->toScale(6, RoundingMode::HalfUp);
        if ($result->isNegative() || $result->isGreaterThan('99999999999999.999999')) {
            throw ValidationException::withMessages(['amount' => __('The amount is outside the supported range.')]);
        }
        return $result;
    }
}
