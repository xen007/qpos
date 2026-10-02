<?php

namespace App\Services;

use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Support\QuantityDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductUnitService
{
    public function save(Product $product, array $data, ?ProductUnit $existing = null): ProductUnit
    {
        return DB::transaction(function () use ($product, $data, $existing) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($product->allows_fractional === null) {
                throw ValidationException::withMessages(['factor' => __('Set the fractional quantity rule on the product first.')]);
            }
            $factor = QuantityDecimal::parse($data['factor'], 'factor', true);
            $reference = $product->productUnits()->where('is_reference', true)->first();
            if ($existing) {
                $existing = $product->productUnits()->whereKey($existing->id)->lockForUpdate()->firstOrFail();
            }
            $isReference = $existing?->is_reference ?? !$reference;
            $unit = Unit::whereKey($data['unit_id'])->lockForUpdate()->firstOrFail();
            if (!$unit->is_active && (int) $unit->id !== (int) $existing?->unit_id) {
                throw ValidationException::withMessages(['unit_id' => __('Select an active unit.')]);
            }
            if ($isReference && (!$factor->isEqualTo('1') || (int) $data['unit_id'] !== (int) $product->unit_id || !$data['is_active'])) {
                throw ValidationException::withMessages(['factor' => __('The reference packaging must remain active, use the base unit and have factor 1.')]);
            }
            if (!$reference && !$isReference) {
                throw ValidationException::withMessages(['factor' => __('Create the reference packaging first.')]);
            }
            if ($existing && OrderProduct::where('product_unit_id', $existing->id)->exists()
                && ((int) $data['unit_id'] !== (int) $existing->unit_id || !$factor->isEqualTo($existing->factor))) {
                throw ValidationException::withMessages(['factor' => __('A packaging used in sales cannot change its unit or factor.')]);
            }
            if ($product->productUnits()->where('code', $data['code'])->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
                throw ValidationException::withMessages(['code' => __('The packaging code is already used for this product.')]);
            }
            $values = array_intersect_key($data, array_flip(['unit_id', 'code', 'label', 'is_active']));
            $values['factor'] = (string) $factor->toScale(6);
            $values['is_reference'] = $isReference;
            if (!$existing && $isReference && \App\Support\PricingSchema::ready()) {
                $values['sale_price_ttc'] = $product->catalogue_price_ttc;
                $values['reference_purchase_cost'] = $product->catalogue_reference_cost;
            }
            $packaging = $existing ?? $product->productUnits()->make();
            $packaging->fill($values)->save();
            return $packaging;
        });
    }
}
