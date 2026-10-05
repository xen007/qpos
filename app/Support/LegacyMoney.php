<?php
namespace App\Support;
use App\Models\Product;
use Brick\Math\RoundingMode;

// Compatibility values for the existing DOUBLE(10,2) consumers.
// Exact amounts are retained separately by ReferencePricingService.
final class LegacyMoney
{
    public static function compatible(array $values, ?Product $product = null): array
    {
        if (!PricingSchema::ready()) { return $values; }
        foreach (['price', 'purchase_price', 'discount'] as $field) {
            if (!isset($values[$field])) { continue; }
            $amount = MoneyDecimal::parse($values[$field], $field);
            $values[$field] = $amount->isLessThanOrEqualTo('99999999.99')
                ? (string) $amount->toScale(2, RoundingMode::HalfUp)
                : (string) ($product?->{$field} ?? '0');
        }
        return $values;
    }
}
