<?php

namespace App\Services;

use App\Support\QuantityDecimal;
use Brick\Math\{BigDecimal, BigInteger, BigRational, RoundingMode};

/** Decimal calculation shared by previews and scheduled drafts. Quantities are base units unless labelled packs. */
final class PurchaseSuggestionCalculator
{
    public function calculate(array $data): array
    {
        $available = QuantityDecimal::parse($data['available'], 'available');
        $optimal = QuantityDecimal::parse($data['optimal'], 'optimal');
        $incoming = QuantityDecimal::parse($data['incoming'] ?? '0', 'incoming');
        $daily = QuantityDecimal::parse($data['daily'] ?? '0', 'daily');
        $delay = $data['delay_days'] ?? null;
        $horizon = (int) ($delay ?? 0) + (int) ($data['review_days'] ?? 7) + (int) ($data['safety_days'] ?? 3);
        if ($horizon < 0 || $horizon > 1095) throw new \InvalidArgumentException('Unsupported planning horizon');
        $reliable = !empty($data['history_sufficient']) && $delay !== null;
        $target = $reliable ? BigDecimal::max($optimal, $daily->multipliedBy($horizon)) : $optimal;
        $need = BigDecimal::max('0', $target->minus($available)->minus($incoming));
        $packs = BigDecimal::zero();
        $base = BigDecimal::zero();
        if ($need->isPositive()) {
            $factor = QuantityDecimal::parse($data['factor'], 'factor', true);
            $minimum = QuantityDecimal::parse($data['minimum_order'], 'minimum_order', true);
            $multiple = QuantityDecimal::parse($data['order_multiple'], 'order_multiple', true);
            $needSteps = BigRational::of($need)->dividedBy($factor)->dividedBy($multiple)->toScale(0, RoundingMode::Ceiling)->toBigInteger();
            $minimumSteps = BigRational::of($minimum)->dividedBy($multiple)->toScale(0, RoundingMode::Ceiling)->toBigInteger();
            $steps = BigInteger::max($needSteps, $minimumSteps);
            $packs = $multiple->multipliedBy($steps);
            $base = BigDecimal::of(QuantityDecimal::toBase((string) $packs, (string) $factor, (bool) $data['allows_fractional']));
        }
        $after = $available->plus($incoming)->plus($base);
        return [
            'available' => (string) $available, 'incoming' => (string) $incoming,
            'daily' => (string) $daily, 'history_sufficient' => $reliable,
            'horizon_days' => $horizon, 'target' => (string) $target, 'need' => (string) $need,
            'quantity_packs' => (string) $packs, 'quantity_base' => (string) $base,
            'projected_stock' => (string) $after,
            'exceeds_maximum' => !empty($data['maximum_enabled']) && isset($data['maximum'])
                && $after->isGreaterThan($data['maximum']),
            'critical' => $reliable && $daily->isPositive() && $available->isLessThan($daily->multipliedBy($delay)),
            'reason' => $reliable ? 'consumption_coverage' : ($delay === null ? 'missing_delay' : 'insufficient_history'),
        ];
    }
}
