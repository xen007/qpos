<?php
namespace App\Services;

use App\Models\ProductUnit;
use App\Models\PriceRule;
use App\Models\Promotion;
use App\Support\MoneyDecimal;
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PricingService
{
    // The caller must authorize the operation's shop and customer.
    public function quote(ProductUnit $unit, string $quantity, int $shopId, ?int $customerId = null, ?CarbonInterface $at = null): array
    {
        $unit->loadMissing('product');
        if (!$unit->is_active || !$unit->product->status || $unit->product->allows_fractional === null || $unit->sale_price_ttc === null) {
            throw ValidationException::withMessages(['product_unit_id' => __('Configure the active product and packaging before calculating a price.')]);
        }
        $baseQuantity = QuantityDecimal::toBase($quantity, $unit->factor, $unit->product->allows_fractional);
        $qty = QuantityDecimal::parse($quantity, 'quantity', true);
        $at ??= now();
        $eligible = function ($query) use ($unit, $shopId, $customerId, $qty, $at) {
            return $query->where('product_unit_id', $unit->id)->where('is_active', true)
                ->where('minimum_quantity', '<=', (string) $qty)
                ->where(fn ($q) => $q->whereNull('point_of_sale_id')->orWhere('point_of_sale_id', $shopId))
                ->where(fn ($q) => $customerId === null ? $q->whereNull('customer_id') : $q->whereNull('customer_id')->orWhere('customer_id', $customerId))
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at));
        };
        $rules = $eligible(PriceRule::query())->get()->sort(function ($a, $b) {
            $scope = fn ($rule) => ($rule->customer_id ? 2 : 0) + ($rule->point_of_sale_id ? 1 : 0);
            return ($scope($b) <=> $scope($a))
                ?: BigDecimal::of($b->minimum_quantity)->compareTo($a->minimum_quantity)
                ?: ($b->priority <=> $a->priority) ?: ($a->id <=> $b->id);
        });
        $rule = $rules->first();
        $price = MoneyDecimal::parse($rule?->price_ttc ?? $unit->sale_price_ttc, 'price_ttc');
        $gross = MoneyDecimal::rounded($price->multipliedBy($qty));
        $candidates = $eligible(Promotion::query())->get()->map(function ($promotion) use ($price, $qty, $gross) {
            $discount = match ($promotion->kind) {
                'percentage' => $gross->multipliedBy($promotion->value)->multipliedBy('0.01'),
                'fixed' => BigDecimal::of($promotion->value)->multipliedBy($qty),
                'quantity' => $qty->dividedBy((string) ($promotion->buy_quantity + $promotion->free_quantity), 0, RoundingMode::Down)
                    ->multipliedBy((string) $promotion->free_quantity)->multipliedBy($price),
                'bundle' => $qty->dividedBy((string) $promotion->bundle_quantity, 0, RoundingMode::Down)
                    ->multipliedBy(BigDecimal::of($price)->multipliedBy((string) $promotion->bundle_quantity)->minus($promotion->bundle_price)),
                default => BigDecimal::zero(),
            };
            $discount = BigDecimal::max(BigDecimal::zero(), BigDecimal::min($gross, $discount));
            return ['promotion' => $promotion, 'discount' => MoneyDecimal::rounded($discount)];
        })->filter(fn ($row) => $row['discount']->isPositive())->sort(function ($a, $b) {
            return ($b['promotion']->priority <=> $a['promotion']->priority)
                ?: $b['discount']->compareTo($a['discount'])
                ?: ($a['promotion']->id <=> $b['promotion']->id);
        });
        $best = $candidates->first();
        $discount = $best['discount'] ?? BigDecimal::zero()->toScale(6);
        $promotion = $best['promotion'] ?? null;
        return [
            'product_unit_id' => $unit->id, 'point_of_sale_id' => $shopId, 'customer_id' => $customerId,
            'quantity' => (string) $qty->toScale(6), 'factor_used' => $unit->factor, 'base_quantity' => $baseQuantity,
            'price_ttc' => (string) $price, 'gross_total' => (string) $gross,
            'discount_total' => (string) $discount, 'total_ttc' => (string) MoneyDecimal::rounded($gross->minus($discount)),
            'price_rule_id' => $rule?->id, 'promotion_id' => $promotion?->id,
            'price_rule_snapshot' => $rule?->only(['id', 'price_ttc', 'minimum_quantity', 'priority', 'starts_at', 'ends_at', 'customer_id', 'point_of_sale_id']),
            'promotion_snapshot' => $promotion?->only(['id', 'kind', 'value', 'buy_quantity', 'free_quantity', 'bundle_quantity', 'bundle_price', 'minimum_quantity', 'priority', 'starts_at', 'ends_at']),
            'calculated_at' => $at->toIso8601String(), 'calculation_version' => 'phase2-v1',
        ];
    }
}
