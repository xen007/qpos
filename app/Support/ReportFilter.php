<?php

namespace App\Support;

use App\Models\PointOfSale;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class ReportFilter
{
    public array $shops;
    public CarbonImmutable $start;
    public CarbonImmutable $end;
    public ?string $cutoff = null;
    public ?int $seller = null;
    public ?int $category = null;
    public ?int $product = null;
    public string $period = 'month';

    public static function fromRequest(Request $r): self
    {
        abort_unless($r->user() && ! $r->user()->is_suspended, 403);
        abort_unless($r->user()->can('point_of_sale_access'), 403);
        $v = $r->validate([
            'shop_id' => 'nullable|integer|min:1', 'seller_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1', 'product_id' => 'nullable|integer|min:1',
            'period' => 'nullable|in:day,week,month,custom', 'date' => 'nullable|date_format:Y-m-d',
            'date_from' => 'nullable|date_format:Y-m-d', 'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'start_date' => 'nullable|date_format:Y-m-d', 'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);
        $f = new self;
        $f->shops = PointOfSale::accessibleBy($r->user())->pluck('points_of_sale.id')->map(fn ($id) => (int) $id)->all();
        if (! empty($v['shop_id'])) {
            abort_unless(in_array((int) $v['shop_id'], $f->shops, true), 403);
            $f->shops = [(int) $v['shop_id']];
        }
        $anchor = CarbonImmutable::parse($v['date'] ?? now('Africa/Douala')->toDateString(), 'Africa/Douala')->startOfDay();
        $f->period = $v['period'] ?? (isset($v['date_from']) || isset($v['start_date']) ? 'custom' : 'month');
        [$f->start, $f->end] = match ($f->period) {
            'day' => [$anchor, $anchor->addDay()],
            'week' => [$anchor->startOfWeek(), $anchor->startOfWeek()->addWeek()],
            'custom' => [CarbonImmutable::parse($v['date_from'] ?? $v['start_date'] ?? $anchor->subDays(29)->toDateString(), 'Africa/Douala')->startOfDay(), CarbonImmutable::parse($v['date_to'] ?? $v['end_date'] ?? $anchor->toDateString(), 'Africa/Douala')->startOfDay()->addDay()],
            default => [$anchor->startOfMonth(), $anchor->startOfMonth()->addMonth()],
        };
        abort_unless($f->end->greaterThan($f->start) && $f->start->diffInDays($f->end) <= 366, 422, __('Select at most 366 days.'));
        $f->seller = isset($v['seller_id']) ? (int) $v['seller_id'] : null;
        $f->category = isset($v['category_id']) ? (int) $v['category_id'] : null;
        $f->product = isset($v['product_id']) ? (int) $v['product_id'] : null;
        return $f;
    }

    public static function day(int $shop, string $day, ?string $cutoff = null): self
    {
        $f = new self;
        $f->shops = [$shop];
        $f->start = CarbonImmutable::parse($day, 'Africa/Douala')->startOfDay();
        $f->end = $f->start->addDay();
        $f->cutoff = $cutoff;
        $f->period = 'day';
        return $f;
    }

    public function bounds(): array
    {
        return [$this->start->utc()->format('Y-m-d H:i:s'), $this->end->utc()->format('Y-m-d H:i:s')];
    }

    public function limit($query, string $column)
    {
        [$a, $b] = $this->bounds();
        $query->where($column, '>=', $a)->where($column, '<', $b);
        if ($this->cutoff) $query->where($column, '<=', $this->cutoff);
        return $query;
    }

    public function salesOnly(): bool
    {
        return $this->seller !== null || $this->category !== null || $this->product !== null;
    }

    public function label(): string
    {
        return $this->start->toDateString().' / '.$this->end->subDay()->toDateString().' · Africa/Douala';
    }
}
