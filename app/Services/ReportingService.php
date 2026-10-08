<?php

namespace App\Services;

use App\Support\ReportFilter;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

final class ReportingService
{
    public const TYPES = [
        'summary' => 'reports_summary', 'seller' => 'reports_sales', 'shop' => 'reports_sales',
        'category' => 'reports_sales', 'product' => 'reports_sales', 'sales' => 'reports_sales',
        'peaks' => 'reports_sales', 'stock' => 'reports_inventory', 'expiry' => 'reports_inventory',
        'cash' => 'reports_summary', 'expenses' => 'reports_summary', 'history' => 'reports_history',
    ];

    private const KNOWN = '(cs.cost_known = 1 AND cs.currency_code = \'XAF\' AND a.total_cost IS NOT NULL)';

    public function events(ReportFilter $f)
    {
        $costs = DB::table('order_stock_allocations as a')
            ->leftJoin('report_allocation_snapshots as cs', 'cs.order_stock_allocation_id', '=', 'a.id')
            ->join('order_products as cl', 'cl.id', '=', 'a.order_product_id')->join('orders as co', 'co.id', '=', 'cl.order_id')
            ->whereIn('co.point_of_sale_id', $f->shops)->where('co.currency_code', 'XAF')->where('co.sale_state', '<>', 'legacy')
            ->selectRaw('a.order_product_id, SUM(a.base_quantity) as allocated_quantity')
            ->selectRaw('SUM(CASE WHEN '.self::KNOWN.' THEN a.total_cost ELSE 0 END) as cogs')
            ->selectRaw('SUM(CASE WHEN '.self::KNOWN.' THEN 0 ELSE a.base_quantity END) as unknown_quantity')
            ->groupBy('a.order_product_id');
        $f->limit($costs, 'co.created_at');
        $sales = DB::table('order_products as l')->join('orders as o', 'o.id', '=', 'l.order_id')
            ->leftJoin('report_line_snapshots as ls', 'ls.order_product_id', '=', 'l.id')
            ->leftJoinSub($costs, 'costs', 'costs.order_product_id', '=', 'l.id')
            ->where('o.currency_code', 'XAF')->where('o.sale_state', '<>', 'legacy')->whereIn('o.point_of_sale_id', $f->shops);
        $f->limit($sales, 'o.created_at');
        $this->lineFilters($sales, $f);
        $sales->selectRaw("o.created_at as occurred_at, o.point_of_sale_id as shop_id, o.user_id as seller_id, ls.category_id, ls.category_label, l.product_id, l.product_label_snapshot as product_label, 'sale' as event_kind, o.id as source_id")
            ->selectRaw('l.effective_total as net_sales')
            ->selectRaw('COALESCE(costs.cogs, 0) as cogs, COALESCE(costs.unknown_quantity, 0) + GREATEST(COALESCE(l.base_quantity, 0) - COALESCE(costs.allocated_quantity, 0), 0) as unknown_quantity, l.base_quantity as quantity');

        $eligible = $f->limit(DB::table('return_stock_allocations as er')->join('return_items as ei', 'ei.id', '=', 'er.return_item_id')->join('sale_corrections as ec', 'ec.id', '=', 'ei.sale_correction_id')->join('orders as eo', 'eo.id', '=', 'ec.order_id')->whereIn('eo.point_of_sale_id', $f->shops)->where('eo.currency_code', 'XAF'), 'ec.created_at')->select('er.order_stock_allocation_id');
        $returnWindows = DB::table('return_stock_allocations')->whereIn('order_stock_allocation_id', $eligible)
            ->select(['id', 'return_item_id', 'order_stock_allocation_id', 'quantity'])
            ->selectRaw('SUM(quantity) OVER (PARTITION BY order_stock_allocation_id ORDER BY id ROWS UNBOUNDED PRECEDING) as cumulative_quantity')
            ->selectRaw('COALESCE(SUM(quantity) OVER (PARTITION BY order_stock_allocation_id ORDER BY id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING),0) as previous_quantity');
        $returned = DB::query()->fromSub($returnWindows, 'ra')->join('order_stock_allocations as a', 'a.id', '=', 'ra.order_stock_allocation_id')
            ->join('return_items as cr', 'cr.id', '=', 'ra.return_item_id')->join('sale_corrections as cc', 'cc.id', '=', 'cr.sale_correction_id')->join('orders as ro', 'ro.id', '=', 'cc.order_id')
            ->whereIn('ro.point_of_sale_id', $f->shops)->where('ro.currency_code', 'XAF')
            ->leftJoin('report_allocation_snapshots as cs', 'cs.order_stock_allocation_id', '=', 'a.id')
            ->selectRaw('ra.return_item_id, SUM(ra.quantity) as allocated_quantity')
            ->selectRaw('SUM(CASE WHEN '.self::KNOWN.' THEN ROUND(a.total_cost * ra.cumulative_quantity / a.base_quantity,6) - ROUND(a.total_cost * ra.previous_quantity / a.base_quantity,6) ELSE 0 END) as cogs')
            ->selectRaw('SUM(CASE WHEN '.self::KNOWN.' THEN 0 ELSE ra.quantity END) as unknown_quantity')->groupBy('ra.return_item_id');
        $f->limit($returned, 'cc.created_at');
        $returnTotals = DB::table('return_items')->join('sale_corrections as tc', 'tc.id', '=', 'return_items.sale_correction_id')->join('orders as tor', 'tor.id', '=', 'tc.order_id')
            ->whereIn('tor.point_of_sale_id', $f->shops)->where('tor.currency_code', 'XAF')
            ->selectRaw('return_items.sale_correction_id, MAX(return_items.id) as last_id, SUM(return_items.amount) as amount')->groupBy('return_items.sale_correction_id');
        $f->limit($returnTotals, 'tc.created_at');
        $returns = DB::table('return_items as ri')->join('sale_corrections as sc', 'sc.id', '=', 'ri.sale_correction_id')
            ->join('order_products as l', 'l.id', '=', 'ri.order_product_id')->join('orders as o', 'o.id', '=', 'l.order_id')
            ->leftJoin('report_line_snapshots as ls', 'ls.order_product_id', '=', 'l.id')
            ->leftJoinSub($returned, 'rc', 'rc.return_item_id', '=', 'ri.id')->joinSub($returnTotals, 'rt', 'rt.sale_correction_id', '=', 'sc.id')
            ->where('o.currency_code', 'XAF')->where('o.sale_state', '<>', 'legacy')->whereIn('o.point_of_sale_id', $f->shops);
        $f->limit($returns, 'sc.created_at');
        $this->lineFilters($returns, $f);
        $returns->selectRaw("sc.created_at as occurred_at, o.point_of_sale_id as shop_id, o.user_id as seller_id, ls.category_id, ls.category_label, l.product_id, l.product_label_snapshot as product_label, 'return' as event_kind, sc.id as source_id")
            ->selectRaw('-(ri.amount + CASE WHEN ri.id = rt.last_id THEN sc.amount - rt.amount ELSE 0 END) as net_sales')
            ->selectRaw('-CASE WHEN ri.saleable = 1 THEN COALESCE(rc.cogs, 0) ELSE 0 END as cogs, -CASE WHEN ri.saleable = 1 THEN COALESCE(rc.unknown_quantity, 0) + GREATEST(ri.base_quantity - COALESCE(rc.allocated_quantity, 0), 0) ELSE 0 END as unknown_quantity, -ri.base_quantity as quantity');
        return DB::query()->fromSub($sales->unionAll($returns), 'events');
    }

    private function lineFilters($q, ReportFilter $f): void
    {
        if ($f->seller) $q->where('o.user_id', $f->seller);
        if ($f->category) $q->where('ls.category_id', $f->category);
        if ($f->product) $q->where('l.product_id', $f->product);
    }

    public function summary(ReportFilter $f): array
    {
        $s = (array) $this->events($f)->selectRaw("COALESCE(SUM(net_sales),0) as net_sales, COALESCE(SUM(cogs),0) as cogs, COALESCE(SUM(ABS(unknown_quantity)),0) as unknown_quantity, COUNT(DISTINCT CASE WHEN event_kind = 'sale' THEN source_id END) as sales_count, COUNT(DISTINCT CASE WHEN event_kind = 'return' THEN source_id END) as returns_count")->first();
        $s['gross_margin'] = (string) BigDecimal::of($s['net_sales'])->minus($s['cogs']);
        $s['incomplete_cost'] = ! BigDecimal::of($s['unknown_quantity'])->isZero();
        $s['expenses'] = $f->salesOnly() ? null : (string) $this->expenseQuery($f)->sum('e.amount');
        $s['management_result'] = $s['expenses'] === null ? null : (string) BigDecimal::of($s['gross_margin'])->minus($s['expenses']);
        $s['treasury'] = $f->salesOnly() ? [] : $this->treasury($f);
        $s['cash_difference'] = $f->salesOnly() ? null : (string) $this->cashQuery($f)->sum('s.difference');
        $manual = $f->limit(DB::table('cash_movements as cm')->join('cash_sessions as cs', 'cs.id', '=', 'cm.cash_session_id')->whereIn('cs.point_of_sale_id', $f->shops)->where('cm.kind', 'manual'), 'cm.occurred_at')
            ->selectRaw("COALESCE(SUM(CASE WHEN cm.direction = 'in' THEN cm.amount ELSE 0 END),0) as manual_in, COALESCE(SUM(CASE WHEN cm.direction = 'out' THEN cm.amount ELSE 0 END),0) as manual_out")->first();
        $s['manual_in'] = (string) $manual->manual_in; $s['manual_out'] = (string) $manual->manual_out;
        $s['opening_floats'] = (string) $f->limit(DB::table('cash_sessions')->whereIn('point_of_sale_id', $f->shops), 'opened_at')->sum('opening_amount');
        $cutoff = $f->cutoff ?? min(now('UTC')->format('Y-m-d H:i:s'), $f->end->utc()->subSecond()->format('Y-m-d H:i:s'));
        $s['native_debt'] = $f->salesOnly() ? null : $this->debtAt($f, $cutoff);
        $newDebt = $f->limit(DB::table('orders')->whereIn('point_of_sale_id', $f->shops)->where('currency_code', 'XAF')->where('sale_state', '<>', 'legacy'), 'created_at');
        $s['new_debt'] = (string) ($newDebt->selectRaw("COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(checkout_snapshot,'$.due')) AS DECIMAL(20,6))),0) as amount")->value('amount') ?? '0');
        if ($f->salesOnly()) $s['new_debt'] = null;
        $s['open_sessions'] = DB::table('cash_sessions')->whereIn('point_of_sale_id', $f->shops)->where('opened_at', '<=', $cutoff)
            ->where(fn ($q) => $q->whereNull('closed_at')->orWhere('closed_at', '>', $cutoff))->count();
        return $s;
    }

    public function treasury(ReportFilter $f): array
    {
        // Supplier occurred_at is already Africa/Douala; native customer payment is UTC.
        // Apply range predicates to raw columns so the existing date indexes remain usable.
        $base = fn () => DB::table('payments')->whereIn('point_of_sale_id', $f->shops)->where('currency_code', 'XAF')->where('provenance', 'live');
        $native = $base()->whereNotNull('receipt_snapshot');
        $f->limit($native, 'occurred_at');
        $supplier = $base()->whereNull('receipt_snapshot')->whereNotNull('supplier_id')
            ->where('occurred_at', '>=', $f->start->format('Y-m-d H:i:s'))->where('occurred_at', '<', $f->end->format('Y-m-d H:i:s'));
        if ($f->cutoff) $supplier->where('occurred_at', '<=', \Carbon\CarbonImmutable::parse($f->cutoff, 'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s'));
        $select = "method, SUM(CASE WHEN direction = 'incoming' THEN net_amount ELSE 0 END) as incoming, SUM(CASE WHEN direction = 'outgoing' THEN net_amount ELSE 0 END) as outgoing";
        $native->selectRaw($select)->groupBy('method');
        $supplier->selectRaw($select)->groupBy('method');
        $payments = DB::query()->fromSub($native->unionAll($supplier), 'flows')->selectRaw('method, SUM(incoming) as incoming, SUM(outgoing) as outgoing')->groupBy('method')->get()->keyBy('method');
        $expenses = $this->expenseQuery($f)->selectRaw('e.method, SUM(e.amount) as amount')->groupBy('e.method')->pluck('amount', 'method');
        return collect(['cash', 'card', 'transfer'])->map(function ($m) use ($payments, $expenses) {
            $p = $payments->get($m);
            $in = $p?->incoming ?? '0'; $out = $p?->outgoing ?? '0'; $expense = $expenses->get($m, '0');
            return ['method' => $m, 'incoming' => (string) $in, 'outgoing' => (string) $out, 'expenses' => (string) $expense, 'net' => (string) BigDecimal::of($in)->minus($out)->minus($expense)];
        })->all();
    }

    public function debtAt(ReportFilter $f, string $cutoff): string
    {
        $paid = DB::table('payment_allocations as pa')->join('payments as p', 'p.id', '=', 'pa.payment_id')
            ->whereIn('p.point_of_sale_id', $f->shops)->where('p.currency_code', 'XAF')->whereNotNull('p.receipt_snapshot')->where('p.direction', 'incoming')->where('p.occurred_at', '<=', $cutoff)
            ->whereNotNull('pa.order_id')->selectRaw('pa.order_id, SUM(pa.amount) as amount')->groupBy('pa.order_id');
        $reductions = DB::table('sale_corrections as sc')->join('orders as so', 'so.id', '=', 'sc.order_id')
            ->whereIn('so.point_of_sale_id', $f->shops)->where('so.currency_code', 'XAF')->where('sc.created_at', '<=', $cutoff)
            ->selectRaw('sc.order_id, SUM(sc.debt_reduction) as amount')->groupBy('sc.order_id');
        return (string) DB::table('orders as o')->whereIn('o.point_of_sale_id', $f->shops)->where('o.currency_code', 'XAF')->where('o.sale_state', '<>', 'legacy')->where('o.created_at', '<=', $cutoff)
            ->leftJoinSub($paid, 'dp', 'dp.order_id', '=', 'o.id')->leftJoinSub($reductions, 'dr', 'dr.order_id', '=', 'o.id')
            ->selectRaw('COALESCE(SUM(GREATEST(o.total - o.credit_used - COALESCE(o.exchange_value,0) - COALESCE(dp.amount,0) - COALESCE(dr.amount,0),0)),0) as amount')->value('amount');
    }

    public function daily(ReportFilter $f): array
    {
        return $this->events($f)->selectRaw("DATE(DATE_ADD(occurred_at, INTERVAL 1 HOUR)) as label, SUM(net_sales) as value")
            ->groupBy('label')->orderBy('label')->get()->map(fn ($r) => (array) $r)->all();
    }

    public function peaks(ReportFilter $f): array
    {
        $hours = $this->events($f)->selectRaw('HOUR(DATE_ADD(occurred_at, INTERVAL 1 HOUR)) as hour, SUM(net_sales) as amount')->groupBy('hour')->pluck('amount', 'hour');
        $days = (int) $f->start->diffInDays($f->end);
        $rows = [];
        for ($h = 0; $h < 24; $h++) {
            $total = (string) $hours->get($h, '0');
            $rows[] = ['hour' => sprintf('%02d:00', $h), 'net_sales' => $total, 'average' => (string) BigDecimal::of($total)->dividedBy($days, 6, RoundingMode::HalfUp), 'days' => $days];
        }
        $sort = function ($a, $b) {
            $cmp = BigDecimal::of($a['average'])->compareTo($b['average']);
            return $cmp ?: strcmp($a['hour'], $b['hour']);
        };
        $low = $rows; usort($low, $sort);
        $high = $rows; usort($high, fn ($a, $b) => BigDecimal::of($b['average'])->compareTo($a['average']) ?: strcmp($a['hour'], $b['hour']));
        return ['rows' => $rows, 'peaks' => array_slice($high, 0, 3), 'troughs' => array_slice($low, 0, 3), 'days' => $days];
    }

    public function grouped(ReportFilter $f, string $type)
    {
        $q = $this->events($f);
        $group = match ($type) {'seller' => 'seller_id', 'shop' => 'shop_id', 'category' => 'category_id', default => 'product_id'};
        if ($type === 'seller') $q->leftJoin('users as u', 'u.id', '=', 'events.seller_id')->selectRaw("COALESCE(u.name, '—') as label")->groupBy('u.name');
        elseif ($type === 'shop') $q->join('points_of_sale as p', 'p.id', '=', 'events.shop_id')->selectRaw('p.name as label')->groupBy('p.name');
        elseif ($type === 'category') $q->selectRaw("COALESCE(events.category_label, '—') as label")->groupBy('events.category_label');
        else $q->selectRaw("COALESCE(events.product_label, '—') as label")->groupBy('events.product_label');
        return $q->addSelect('events.'.$group)->selectRaw('SUM(net_sales) as net_sales, SUM(cogs) as cogs, SUM(net_sales) - SUM(cogs) as gross_margin, SUM(ABS(unknown_quantity)) as unknown_quantity, SUM(quantity) as quantity')->groupBy('events.'.$group)->orderByDesc('net_sales')->orderBy('events.'.$group);
    }

    public function expenseQuery(ReportFilter $f)
    {
        $q = DB::table('expenses as e')->whereIn('e.point_of_sale_id', $f->shops)->where('e.currency_code', 'XAF');
        return $f->limit($q, 'e.occurred_at');
    }

    public function cashQuery(ReportFilter $f)
    {
        return $f->limit(DB::table('cash_sessions as s')->whereIn('s.point_of_sale_id', $f->shops)->where('s.currency_code', 'XAF')->where('s.state', 'closed'), 's.closed_at');
    }

    public function stockQuery(ReportFilter $f)
    {
        $q = DB::table('batch_stock as bs')->join('product_batches as b', 'b.id', '=', 'bs.product_batch_id')
            ->join('products as p', 'p.id', '=', 'b.product_id')->join('points_of_sale as shop', 'shop.id', '=', 'bs.point_of_sale_id')
            ->whereIn('bs.point_of_sale_id', $f->shops)->where(fn ($q) => $q->where('bs.saleable_quantity', '>', 0)->orWhere('bs.unsaleable_quantity', '>', 0));
        if ($f->product) $q->where('p.id', $f->product);
        if ($f->category) $q->where('p.category_id', $f->category);
        return $q;
    }

    public function stockTotals(ReportFilter $f): array
    {
        return (array) $this->stockQuery($f)->selectRaw("COALESCE(SUM(CASE WHEN b.cost_unknown = 0 AND b.unit_cost IS NOT NULL AND b.currency_code = 'XAF' THEN ROUND(bs.saleable_quantity * b.unit_cost,6) ELSE 0 END),0) as saleable_value, COALESCE(SUM(CASE WHEN b.cost_unknown = 0 AND b.unit_cost IS NOT NULL AND b.currency_code = 'XAF' THEN ROUND(bs.unsaleable_quantity * b.unit_cost,6) ELSE 0 END),0) as unsaleable_value, COALESCE(SUM(CASE WHEN b.cost_unknown = 1 OR b.unit_cost IS NULL OR b.currency_code IS NULL THEN bs.saleable_quantity + bs.unsaleable_quantity ELSE 0 END),0) as unknown_quantity, COALESCE(SUM(CASE WHEN b.currency_code IS NOT NULL AND b.currency_code <> 'XAF' THEN bs.saleable_quantity + bs.unsaleable_quantity ELSE 0 END),0) as foreign_quantity")->first();
    }

    public function report(ReportFilter $f, string $type): array
    {
        $meta = ['type' => $type, 'filter' => $f, 'currency' => $type === 'history' ? '—' : 'XAF'];
        if ($type === 'summary') {
            $s = $this->summary($f);
            $rows = collect(['net_sales', 'cogs', 'gross_margin', 'expenses', 'management_result', 'unknown_quantity', 'cash_difference', 'open_sessions', 'sales_count', 'returns_count'])->map(fn ($key) => ['indicator' => __('reporting.'.$key), 'value' => $s[$key] ?? '—'])->all();
            foreach (['manual_in', 'manual_out', 'opening_floats', 'new_debt', 'native_debt'] as $key) $rows[] = ['indicator' => __('reporting.'.$key), 'value' => $s[$key]];
            foreach ($s['treasury'] as $flow) foreach (['incoming', 'outgoing', 'expenses', 'net'] as $key) $rows[] = ['indicator' => __('reporting.treasury').' · '.__('reporting.'.($flow['method'] === 'cash' ? 'cash_method' : $flow['method'])).' · '.__('reporting.'.$key), 'value' => $flow[$key]];
            return $meta + ['columns' => ['indicator', 'value'], 'rows' => $rows, 'summary' => $s];
        }
        if ($type === 'peaks') {
            $peaks = $this->peaks($f);
            return $meta + ['columns' => ['hour', 'net_sales', 'average', 'days'], 'rows' => $peaks['rows'], 'peaks' => $peaks];
        }
        if (in_array($type, ['seller', 'shop', 'category', 'product'], true)) return $meta + ['columns' => ['label', 'net_sales', 'cogs', 'gross_margin', 'unknown_quantity', 'quantity'], 'query' => $this->grouped($f, $type)];
        if ($type === 'sales') {
            return $meta + ['columns' => ['occurred_at', 'event_kind', 'source_id', 'product_label', 'net_sales', 'cogs', 'unknown_quantity'], 'query' => $this->events($f)->orderByDesc('occurred_at')->orderByDesc('source_id')];
        }
        if (in_array($type, ['stock', 'expiry'], true)) {
            $q = $this->stockQuery($f);
            if ($type === 'expiry') {
                $v = request('expiry', '90');
                if ($v === 'unknown') $q->where('b.expiry_status', 'unknown');
                elseif ($v === 'expired') $q->where('b.expires_on', '<', now('Africa/Douala')->toDateString());
                else $q->whereNotNull('b.expires_on')->where('b.expires_on', '<=', now('Africa/Douala')->addDays(in_array($v, ['7', '30', '90'], true) ? (int) $v : 90)->toDateString());
            }
            $q->select(['b.id', 'shop.name as shop', 'p.name as product_label', 'b.batch_number', 'b.expires_on', 'b.estimated_expiry', 'b.auto_generated', 'b.provenance', 'b.cost_unknown', 'b.currency_code', 'b.unit_cost', 'bs.saleable_quantity', 'bs.unsaleable_quantity'])
                ->selectRaw("CASE WHEN b.cost_unknown = 0 AND b.unit_cost IS NOT NULL AND b.currency_code = 'XAF' THEN ROUND(bs.saleable_quantity * b.unit_cost,6) ELSE NULL END as saleable_value")
                ->orderBy('b.expires_on')->orderBy('b.received_at')->orderBy('b.id');
            return $meta + ['columns' => ['shop', 'product_label', 'batch_number', 'expires_on', 'estimated_expiry', 'auto_generated', 'cost_unknown', 'currency_code', 'saleable_quantity', 'unsaleable_quantity', 'saleable_value'], 'query' => $q, 'stock_totals' => $this->stockTotals($f)];
        }
        if ($type === 'cash') {
            $q = $this->cashQuery($f)->join('users as u', 'u.id', '=', 's.user_id')->join('points_of_sale as p', 'p.id', '=', 's.point_of_sale_id')
                ->select(['s.id', 'p.name as shop', 'u.name as seller', 's.closed_at as occurred_at', 's.opening_amount', 's.expected_amount', 's.counted_amount', 's.difference', 's.reason'])->orderByDesc('s.closed_at')->orderByDesc('s.id');
            return $meta + ['columns' => ['shop', 'seller', 'occurred_at', 'opening_amount', 'expected_amount', 'counted_amount', 'difference', 'reason'], 'query' => $q];
        }
        if ($type === 'expenses') {
            $q = $this->expenseQuery($f)->join('expense_categories as ec', 'ec.id', '=', 'e.expense_category_id')->join('points_of_sale as p', 'p.id', '=', 'e.point_of_sale_id')
                ->select(['e.id', 'p.name as shop', 'ec.name as category_label', 'e.occurred_at', 'e.method', 'e.amount', 'e.description'])->orderByDesc('e.occurred_at')->orderByDesc('e.id');
            return $meta + ['columns' => ['shop', 'category_label', 'occurred_at', 'method', 'amount', 'description'], 'query' => $q];
        }
        // Dates and currency of legacy documents remain raw. Null-shop history is
        // intentionally restricted by the controller to an explicitly authorised Admin.
        $q = DB::table('orders as o')->where('o.sale_state', 'legacy')
            ->where(fn ($q) => $q->whereIn('o.point_of_sale_id', $f->shops)->orWhereNull('o.point_of_sale_id'))
            ->where('o.created_at', '>=', $f->start->format('Y-m-d H:i:s'))->where('o.created_at', '<', $f->end->format('Y-m-d H:i:s'));
        $q->select(['o.id', 'o.point_of_sale_id as shop_id', 'o.created_at as legacy_date', 'o.currency_code', 'o.total', 'o.paid', 'o.due'])->orderByDesc('o.created_at')->orderByDesc('o.id');
        return $meta + ['columns' => ['id', 'shop_id', 'legacy_date', 'currency_code', 'total', 'paid', 'due'], 'query' => $q];
    }
}
