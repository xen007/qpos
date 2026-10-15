<?php

namespace App\Services;

use App\Models\{PointOfSale, Product, ProductUnit, User};
use Brick\Math\{BigDecimal as Decimal, RoundingMode};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderSuggestionService
{
    public function products(array $filters = [])
    {
        $query = Product::query()->where('status', true);
        if (!empty($filters['category_id'])) $query->where('category_id', $filters['category_id']);
        if (!empty($filters['supplier_id'])) $query->whereExists(fn ($q) => $q->selectRaw('1')->from('supplier_catalog as fc')
            ->whereColumn('fc.product_id', 'products.id')->where('fc.supplier_id', $filters['supplier_id'])->where('fc.is_active', true));
        if (!empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('sku', 'like', $search));
        }
        return $query;
    }

    public function getSuggestions(int $actor, int $shop, array $productIds, array $options = []): array
    {
        $user = app(ReplenishmentSettings::class)->actor($actor, $shop);
        if (count($productIds) > 200) throw new \InvalidArgumentException('At most 200 products per batch');
        $settings = array_replace(app(ReplenishmentSettings::class)->get($shop), array_intersect_key($options, array_flip(['window_days', 'review_days', 'safety_days', 'minimum_history_days'])));
        if (!in_array((int) $settings['window_days'], [30, 60, 90], true)) throw new \InvalidArgumentException('Unsupported consumption window');
        $today = CarbonImmutable::now('Africa/Douala')->startOfDay();
        $from = $today->subDays((int) $settings['window_days'])->utc()->format('Y-m-d H:i:s');
        $to = $today->utc()->format('Y-m-d H:i:s');
        $products = app(StockAvailability::class)->attach(Product::with('category')->whereIn('products.id', $productIds)->where('status', true), $shop)->get();
        $ids = $products->pluck('id')->all();
        if (!$ids) return [];
        $sales = DB::table('order_products as l')->join('orders as o', 'o.id', '=', 'l.order_id')
            ->whereIn('l.product_id', $ids)->where('o.point_of_sale_id', $shop)->where('o.currency_code', 'XAF')
            ->where('o.sale_state', '<>', 'legacy')->whereNotNull('l.base_quantity')->where('o.created_at', '<', $to)
            ->selectRaw('l.product_id, MIN(o.created_at) as first_seen, SUM(CASE WHEN o.created_at >= ? THEN l.base_quantity ELSE 0 END) AS quantity', [$from])
            ->groupBy('l.product_id')->get()->keyBy('product_id');
        $returns = DB::table('return_items as ri')->join('sale_corrections as sc', 'sc.id', '=', 'ri.sale_correction_id')
            ->join('order_products as l', 'l.id', '=', 'ri.order_product_id')->join('orders as o', 'o.id', '=', 'l.order_id')
            ->whereIn('l.product_id', $ids)->where('o.point_of_sale_id', $shop)->where('o.currency_code', 'XAF')->where('o.sale_state', '<>', 'legacy')
            ->where('sc.created_at', '>=', $from)->where('sc.created_at', '<', $to)
            ->selectRaw('l.product_id, SUM(ri.base_quantity) AS quantity')->groupBy('l.product_id')->pluck('quantity', 'product_id');
        $catalogue = DB::table('supplier_catalog as c')->join('suppliers as s', 's.id', '=', 'c.supplier_id')
            ->whereIn('c.product_id', $ids)->where('c.is_active', true)->where('s.is_active', true)->where('s.is_internal', false)
            ->select('c.*', 's.name as supplier_name')->orderByDesc('c.preferred')->orderBy('c.id')->get()->groupBy('product_id');
        $units = ProductUnit::whereIn('id', $catalogue->flatten()->pluck('product_unit_id'))->where('is_active', true)->get()->keyBy('id');
        $policyRows = DB::table('stock_policies')->where(fn ($q) => $q->whereNull('point_of_sale_id')->orWhere('point_of_sale_id', $shop))
            ->where(fn ($q) => $q->whereNull('product_id')->orWhereIn('product_id', $ids))->get()->map(fn ($r) => (array) $r)->all();
        $received = DB::table('purchase_receipt_items as ri')->join('purchase_receipts as r', 'r.id', '=', 'ri.purchase_receipt_id')
            ->join('purchase_items as rpi', 'rpi.id', '=', 'ri.purchase_item_id')->where('r.point_of_sale_id', $shop)->whereIn('rpi.product_id', $ids)
            ->where('r.status', 'received')->selectRaw('ri.purchase_item_id, SUM(ri.entered_quantity) AS quantity')->groupBy('ri.purchase_item_id');
        $pending = DB::table('purchase_items as pi')->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->leftJoin('purchase_orders as po', 'po.purchase_id', '=', 'p.id')->leftJoinSub($received, 'r', 'r.purchase_item_id', '=', 'pi.id')
            ->whereIn('pi.product_id', $ids)->where('p.point_of_sale_id', $shop)->where('p.currency_code', 'XAF')->whereNull('p.cancelled_at')
            ->whereIn('p.receipt_status', ['pending', 'partial'])->whereNotNull('pi.entered_quantity')->whereNotNull('pi.factor_used')
            ->selectRaw('pi.product_id, po.expected_date, SUM(GREATEST(0, pi.entered_quantity - COALESCE(r.quantity,0)) * pi.factor_used) AS quantity')
            ->groupBy('pi.product_id', 'po.expected_date')->get()->groupBy('product_id');
        $expiry = DB::table('batch_stock as s')->join('product_batches as b', 'b.id', '=', 's.product_batch_id')
            ->where('s.point_of_sale_id', $shop)->whereIn('b.product_id', $ids)->where('b.expiry_status', 'dated')
            ->where('b.expires_on', '>=', $today->toDateString())->where('s.saleable_quantity', '>', 0)
            ->selectRaw('b.product_id, b.expires_on, SUM(s.saleable_quantity) AS quantity')->groupBy('b.product_id', 'b.expires_on')
            ->get()->groupBy('product_id');
        $drafts = DB::table('purchase_order_items as di')->join('purchase_orders as dp','dp.id','=','di.purchase_order_id')
            ->where('dp.point_of_sale_id',$shop)->where('dp.status','draft')->whereIn('di.product_id',$ids)
            ->selectRaw('di.product_id, MIN(dp.id) AS draft_id')->groupBy('di.product_id')->pluck('draft_id','product_id');
        $result = [];
        foreach ($products as $product) {
            $entries = $catalogue->get($product->id, collect());
            $chosen = !empty($options['supplier_id']) ? $entries->firstWhere('supplier_id', (int) $options['supplier_id'])
                : ($entries->firstWhere('preferred', 1) ?? ($entries->count() === 1 ? $entries->first() : null));
            $unit = $chosen ? $units->get($chosen->product_unit_id) : null;
            if ($unit && (int) $unit->product_id !== (int) $product->id) $unit = null;
            $packReady = $unit !== null && $product->allows_fractional !== null;
            $policy = app(StockPolicyService::class)->effective($shop, $product->id, $policyRows);
            $sold = $sales->get($product->id);
            $observed = $sold ? min((int) $settings['window_days'], max(0, (int) CarbonImmutable::parse($sold->first_seen, 'UTC')->setTimezone('Africa/Douala')->startOfDay()->diffInDays($today))) : 0;
            $net = Decimal::of($sold->quantity ?? '0')->minus($returns[$product->id] ?? '0');
            $daily = $observed > 0 ? Decimal::max('0', $net)->dividedBy($observed, 6, RoundingMode::HalfUp) : Decimal::zero();
            $delay = $chosen ? (int) $chosen->delay_days : null;
            $horizon = (int) ($delay ?? 0) + (int) $settings['review_days'] + (int) $settings['safety_days'];
            $deadline = $today->addDays($horizon)->toDateString();
            $incoming = $overdue = $late = $undated = Decimal::zero();
            foreach ($pending->get($product->id, collect()) as $row) {
                $qty = Decimal::of($row->quantity)->toScale(6);
                if (!$row->expected_date) $undated = $undated->plus($qty);
                elseif ($row->expected_date < $today->toDateString()) $overdue = $overdue->plus($qty);
                elseif ($row->expected_date <= $deadline) $incoming = $incoming->plus($qty);
                else $late = $late->plus($qty);
            }
            $atRisk = Decimal::zero();
            foreach ($expiry->get($product->id, collect()) as $lot) if ($lot->expires_on <= $deadline) $atRisk = $atRisk->plus($lot->quantity);
            $available = (string) ($product->getRawOriginal('stock_available') ?? '0');
            $calculation = app(PurchaseSuggestionCalculator::class)->calculate([
                ...$policy, 'available' => $available, 'incoming' => (string) $incoming, 'daily' => (string) $daily,
                'history_sufficient' => $observed >= (int) $settings['minimum_history_days'],
                'delay_days' => $delay, 'review_days' => $settings['review_days'], 'safety_days' => $settings['safety_days'],
                'factor' => $packReady ? $unit->factor : '1', 'minimum_order' => $packReady ? $chosen->minimum_order : ($product->allows_fractional ? '0.000001' : '1'),
                'order_multiple' => $packReady ? $chosen->order_multiple : ($product->allows_fractional ? '0.000001' : '1'),
                'allows_fractional' => (bool) $product->allows_fractional,
            ]);
            $warnings = [];
            if (!$chosen) $warnings[] = $entries->count() > 1 ? 'ambiguous_supplier' : 'missing_supplier';
            if (!$unit) $warnings[] = 'missing_packaging';
            if ($product->allows_fractional === null) $warnings[] = 'missing_fraction_rule';
            if ($chosen && $chosen->estimated_cost === null) $warnings[] = 'unknown_cost';
            if ($chosen?->price_on && $chosen->price_on < $today->subDays(90)->toDateString()) $warnings[] = 'old_price';
            if (!$calculation['history_sufficient']) $warnings[] = $calculation['reason'];
            if ($net->isNegative()) $warnings[] = 'returns_exceed_sales';
            if ($overdue->isPositive()) $warnings[] = 'overdue_incoming';
            if ($late->isPositive()) $warnings[] = 'late_incoming';
            if ($undated->isPositive()) $warnings[] = 'undated_incoming';
            if ($atRisk->isPositive()) $warnings[] = 'expiry_risk';
            if ($calculation['exceeds_maximum']) $warnings[] = 'exceeds_maximum';
            $result[$product->id] = [
                'product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'allows_fractional' => (bool) $product->allows_fractional,
                'supplier_id' => $chosen?->supplier_id, 'supplier_name' => $chosen?->supplier_name,
                'supplier_reference' => $chosen?->supplier_reference, 'product_unit_id' => $unit?->id,
                'unit_label' => $unit?->label, 'factor' => $unit?->factor, 'unit_price' => $chosen?->estimated_cost,
                'price_on' => $chosen?->price_on, 'policy' => $policy, ...$calculation,
                'observed_days' => $observed, 'window_days' => (int) $settings['window_days'], 'net_quantity' => (string) $net,
                'delay_days' => $delay, 'review_days' => (int) $settings['review_days'], 'safety_days' => (int) $settings['safety_days'],
                'overdue_incoming' => (string) $overdue, 'late_incoming' => (string) $late, 'undated_incoming' => (string) $undated,
                'expiry_at_risk' => (string) $atRisk, 'expired_stock' => (string) ($product->getRawOriginal('stock_expired') ?? '0'),
                'expected_date' => $delay !== null ? $today->addDays($delay)->toDateString() : null,
                'can_draft' => $chosen !== null && $unit !== null && $product->allows_fractional !== null && Decimal::of($calculation['quantity_packs'])->isPositive(),
                'warnings' => array_values(array_unique($warnings)), 'existing_draft_id' => $drafts[$product->id] ?? null, 'transfers' => [],
            ];
        }
        if ($options['include_transfers'] ?? true) $this->transfers($result, $user, $shop);
        return array_values($result);
    }

    private function transfers(array &$rows, User $actor, int $destination): void
    {
        if (!$actor->hasAllPermissions(['stock_transfer_dispatch', 'stock_transfer_receive']) || $actor->fixed_point_of_sale_id) return;
        $ids = array_keys(array_filter($rows, fn ($r) => Decimal::of($r['need'])->isPositive()));
        if (!$ids) return;
        $shops = PointOfSale::accessibleBy($actor)->where('is_active', true)->where('points_of_sale.id', '<>', $destination)->orderBy('id')->limit(20)->get();
        foreach ($shops as $shop) {
            $policies = DB::table('stock_policies')->where(fn ($q) => $q->whereNull('point_of_sale_id')->orWhere('point_of_sale_id', $shop->id))
                ->where(fn ($q) => $q->whereNull('product_id')->orWhereIn('product_id', $ids))->get()->map(fn ($r) => (array) $r)->all();
            $stock = app(StockAvailability::class)->attach(Product::whereIn('products.id', $ids), $shop->id)->get();
            foreach ($stock as $product) {
                $policy = app(StockPolicyService::class)->effective($shop->id, $product->id, $policies);
                $surplus = Decimal::max('0', Decimal::of($product->getRawOriginal('stock_available') ?? '0')->minus($policy['optimal']));
                $quantity = Decimal::min($surplus, Decimal::of($rows[$product->id]['need']));
                if (!$rows[$product->id]['allows_fractional']) $quantity = $quantity->toScale(0, RoundingMode::Down);
                if ($quantity->isPositive()) $rows[$product->id]['transfers'][] = ['shop_id' => $shop->id, 'shop_name' => $shop->name,
                    'quantity_base' => (string) $quantity];
            }
        }
    }
}
