<?php
namespace App\Services;
use App\Models\Product;
use App\Models\Promotion;
use App\Support\MoneyDecimal;
use App\Support\PricingSchema;

class ReferencePricingService
{
    public function sync(Product $product, array $values, bool $creating = false): void
    {
        if (!PricingSchema::ready()) { return; }
        $native = [];
        if (isset($values['price'])) { $native['catalogue_price_ttc'] = (string) MoneyDecimal::parse($values['price'], 'price'); }
        if (isset($values['purchase_price']) && ($creating || auth()->user()?->hasRole('Admin'))) { $native['catalogue_reference_cost'] = (string) MoneyDecimal::parse($values['purchase_price'], 'purchase_price'); }
        if (array_key_exists('discount', $values)) { $native['catalogue_discount'] = $values['discount'] === null ? null : (string) MoneyDecimal::parse($values['discount'], 'discount'); }
        if (array_key_exists('discount_type', $values)) { $native['catalogue_discount_type'] = $values['discount_type']; }
        if ($native) { $product->forceFill($native)->save(); }
        $reference = $product->productUnits()->where('is_reference', true)->lockForUpdate()->first();
        if (!$reference) { return; }
        if (isset($values['price'])) { $reference->sale_price_ttc = (string) MoneyDecimal::parse($values['price'], 'price'); }
        if (isset($values['purchase_price']) && ($creating || auth()->user()?->hasRole('Admin'))) {
            $reference->reference_purchase_cost = (string) MoneyDecimal::parse($values['purchase_price'], 'purchase_price');
        }
        $reference->save();
        if (array_key_exists('discount', $values) || array_key_exists('discount_type', $values)) {
            $discount = MoneyDecimal::parse($values['discount'] ?? (string) ($product->discount ?? '0'), 'discount');
            $promotion = Promotion::firstOrNew(['legacy_product_id' => $product->id]);
            if ($promotion->exists || $discount->isPositive()) {
                $promotion->fill(['product_unit_id' => $reference->id, 'name' => $product->name,
                    'kind' => $values['discount_type'] ?? $product->discount_type ?? 'fixed', 'value' => (string) $discount,
                    'minimum_quantity' => '0', 'priority' => 0, 'is_active' => $discount->isPositive()]);
                $promotion->save();
            }
        }
    }
}
