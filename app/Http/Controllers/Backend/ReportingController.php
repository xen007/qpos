<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\PointOfSale;
use App\Services\DailySummaryService;
use App\Services\ReportingService;
use App\Support\ReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportingController extends Controller
{
    private function authorizeType(Request $r, string $type): ReportFilter
    {
        abort_unless(isset(ReportingService::TYPES[$type]), 404);
        abort_unless($r->user()?->can(ReportingService::TYPES[$type]), 403);
        if ($type === 'history') abort_unless($r->user()->hasRole('Admin'), 403);
        $f = ReportFilter::fromRequest($r);
        if (in_array($type, ['summary', 'cash', 'expenses', 'history'], true)) abort_if($f->salesOnly(), 422, __('reporting.sales_filters_only'));
        if (in_array($type, ['stock', 'expiry'], true)) abort_if($f->seller !== null, 422, __('reporting.sales_filters_only'));
        return $f;
    }

    public function dashboard(Request $r)
    {
        abort_unless($r->user()?->can('dashboard_view'), 403);
        $filter = ReportFilter::fromRequest($r);
        $s = app(ReportingService::class);
        $clients = $s->events($filter)->where('event_kind', 'sale')->join('orders as customer_orders', 'customer_orders.id', '=', 'events.source_id')->join('customers as served', 'served.id', '=', 'customer_orders.customer_id')->where(fn ($q) => $q->whereNull('served.internal_code')->orWhere('served.internal_code', '<>', 'walking'))->distinct()->count('served.id');
        // Multi-shop stock is evaluated per shop; never infer an inaccessible shop.
        $lowStock = collect($filter->shops)->sum(function ($shop) use ($filter) {
            $products = \App\Models\Product::query()->where('status', true)
                ->when($filter->product !== null, fn ($q) => $q->where('products.id', $filter->product))
                ->when($filter->category !== null, fn ($q) => $q->where('products.category_id', $filter->category));
            return DB::query()->fromSub(app(\App\Services\StockAvailability::class)->attach($products, $shop), 'available')
                ->where('stock_available', '>', 0)->where('stock_available', '<', 10)->count();
        });
        return view('backend.reporting.dashboard', $this->choices($r, $filter) + [
            'filter' => $filter, 'summary' => $s->summary($filter), 'daily' => $s->daily($filter),
            'peaks' => $s->peaks($filter), 'stock' => $s->stockTotals($filter), 'types' => ReportingService::TYPES,
            'clients' => $clients, 'lowStock' => $lowStock,
        ]);
    }

    public function statistics(Request $r)
    {
        $dashboard = $this->dashboard($r);
        return view('backend.reporting.statistics', $dashboard->getData());
    }

    public function index(Request $r, string $type)
    {
        $filter = $this->authorizeType($r, $type);
        $report = app(ReportingService::class)->report($filter, $type);
        $rows = isset($report['query']) ? $report['query']->paginate(30)->withQueryString() : collect($report['rows']);
        return view('backend.reporting.report', $this->choices($r, $filter) + compact('report', 'rows', 'filter') + ['types' => ReportingService::TYPES]);
    }

    public function export(Request $r, string $type, string $format)
    {
        $filter = $this->authorizeType($r, $type);
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        $report = app(ReportingService::class)->report($filter, $type);
        $cap = $format === 'pdf' ? 1000 : (int) config('reporting.max_export_rows', 10000);
        $rows = isset($report['query']) ? $report['query']->limit($cap + 1)->get() : collect($report['rows']);
        abort_if($rows->count() > $cap, 422, __('reporting.export_limit', ['limit' => $cap]));
        $data = $rows->map(fn ($row) => collect($report['columns'])->map(fn ($col) => $this->displayValue($col, ((array) $row)[$col] ?? null, $type))->all())->all();
        $headings = array_map(fn ($col) => __('reporting.'.$col), $report['columns']);
        $filename = 'qpos-'.$type.'-'.$filter->start->toDateString().'.'.$format;
        if ($format === 'xlsx') {
            array_unshift($data, [__('reporting.scope'), implode(', ', $filter->shops)], [__('reporting.period'), $filter->label()], [__('reporting.currency_code'), $report['currency']], [__('reporting.warning'), __('reporting.cost_notice')], $headings);
            if (isset($report['peaks'])) foreach (['peaks', 'troughs'] as $rank) $data[] = [__('reporting.top_'.$rank), implode('; ', array_map(fn ($v) => $v['hour'].' ('.$v['average'].' XAF)', $report['peaks'][$rank]))];
            return Excel::download(new ReportExport($data, [__('reporting.'.$type), 'QPOS'], $type), $filename);
        }
        return Pdf::loadView('backend.reporting.report-pdf', compact('report', 'filter', 'data', 'headings'))->setPaper('a4', $type === 'peaks' ? 'portrait' : 'landscape')->download($filename);
    }

    public function displayValue(string $col, mixed $value, string $type): string
    {
        if ($value === null) return '—';
        if ($col === 'occurred_at') return CarbonImmutable::parse($value, 'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s');
        if (in_array($col, ['cost_unknown', 'auto_generated', 'estimated_expiry'], true)) return $value ? __('Yes') : __('No');
        if (in_array($col, ['event_kind', 'method'], true)) return __('reporting.'.($col === 'method' && $value === 'cash' ? 'cash_method' : $value));
        if (in_array($col, ['net_sales','cogs','gross_margin','unknown_quantity','quantity','average','saleable_quantity','unsaleable_quantity','saleable_value','amount','difference','expected_amount','counted_amount','opening_amount','total','paid','due','value'], true) && is_numeric($value)) return \App\Support\SaleFormat::decimal($value);
        return (string) $value;
    }

    private function choices(Request $r, ReportFilter $filter): array
    {
        $shops = PointOfSale::accessibleBy($r->user())->orderBy('name')->get();
        $products = DB::table('products')->whereIn('id', DB::table('product_stock')->whereIn('point_of_sale_id', $filter->shops)->select('product_id'))->orderBy('name')->limit(500)->get(['id', 'name']);
        $sellers = DB::table('users')->whereIn('id', DB::table('orders')->whereIn('point_of_sale_id', $filter->shops)->where('currency_code', 'XAF')->select('user_id'))->orderBy('name')->get(['id', 'name']);
        $categories = DB::table('report_line_snapshots as ls')->join('order_products as l', 'l.id', '=', 'ls.order_product_id')->join('orders as o', 'o.id', '=', 'l.order_id')
            ->whereIn('o.point_of_sale_id', $filter->shops)->whereNotNull('ls.category_id')->select('ls.category_id as id', 'ls.category_label as name')->distinct()->orderBy('name')->get();
        $currentCategories = DB::table('categories')->whereIn('id', DB::table('products')->whereIn('id', DB::table('product_stock')->whereIn('point_of_sale_id', $filter->shops)->select('product_id'))->select('category_id'))->get(['id', 'name']);
        $categories = $categories->merge($currentCategories)->unique(fn ($c) => $c->id.':'.$c->name)->sortBy('name')->values();
        return compact('shops', 'products', 'sellers', 'categories');
    }

    private function authorizeSummary(Request $r): void
    {
        abort_unless($r->user() && ! $r->user()->is_suspended && $r->user()->hasAllPermissions(['daily_summary_view', 'reports_summary', 'reports_sales', 'reports_inventory']), 403);
    }

    public function summaries(Request $r)
    {
        $this->authorizeSummary($r);
        $filter = ReportFilter::fromRequest($r);
        $rows = DB::table('daily_summaries')->whereIn('point_of_sale_id', $filter->shops)->where('business_date', '>=', $filter->start->toDateString())->where('business_date', '<', $filter->end->toDateString())->orderByDesc('business_date')->orderByDesc('version')->paginate(30)->withQueryString();
        $pause = DB::table('reporting_pauses')->where('active_user_id', $r->user()->id)->first();
        $scheduler = DB::table('reporting_runtime')->where('key', 'scheduler_last_run')->value('value');
        $states = DB::table('summary_deliveries as d')->join('daily_summaries as s', 's.id', '=', 'd.daily_summary_id')->whereIn('s.point_of_sale_id', $filter->shops)->selectRaw('d.state, COUNT(*) as total')->groupBy('d.state')->get();
        $mailEnabled = app(\App\Services\SummaryMailApproval::class)->enabled();
        $mailPreview = $r->user()->hasRole('Admin') && $r->user()->hasAllPermissions(['daily_summary_close', 'user_view', 'point_of_sale_manage_all']) ? app(\App\Services\SummaryMailApproval::class)->preview($r->user()) : null;
        return view('backend.reporting.summaries', $this->choices($r, $filter) + compact('filter', 'rows', 'pause', 'scheduler', 'states', 'mailEnabled', 'mailPreview'));
    }

    public function summary(Request $r, int $id, ?string $format = null)
    {
        $this->authorizeSummary($r);
        $row = DB::table('daily_summaries')->whereIn('point_of_sale_id', PointOfSale::accessibleBy($r->user())->select('points_of_sale.id'))->find($id);
        abort_unless($row, 404);
        $data = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
        if ($format === 'pdf') return response(app(DailySummaryService::class)->pdf($row), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="recap-'.$row->business_date.'-v'.$row->version.'.pdf"']);
        $versions = DB::table('daily_summaries')->where('point_of_sale_id', $row->point_of_sale_id)->where('business_date', $row->business_date)->orderBy('version')->get();
        $late = app(DailySummaryService::class)->lateQuery($row)->paginate(30)->withQueryString();
        $deliveries = DB::table('summary_deliveries')->where('daily_summary_id', $id)->orderBy('id')->paginate(30, ['*'], 'delivery_page');
        return view('backend.reporting.summary', compact('row', 'data', 'versions', 'late', 'deliveries'));
    }

    public function closeDay(Request $r)
    {
        $this->authorizeSummary($r);
        abort_unless($r->user()->hasRole('Admin') && $r->user()->can('daily_summary_close'), 403);
        $r->validate(['shop_id' => 'required|integer|min:1']);
        $filter = ReportFilter::fromRequest($r);
        $summary = app(DailySummaryService::class)->generate($filter->shops[0], now('Africa/Douala')->toDateString(), 'manual', $r->user()->id);
        return to_route('backend.admin.reporting.summary', $summary->id)->with('success', __('reporting.day_closed'));
    }

    public function pause(Request $r)
    {
        abort_unless($r->user()?->can('cash_session_manage') && ! $r->user()->is_suspended, 403);
        $r->validate(['shop_id' => 'required|integer|min:1', 'action' => 'required|in:pause,resume']);
        $filter = ReportFilter::fromRequest($r);
        DB::transaction(function () use ($r, $filter) {
            DB::table('users')->where('id', $r->user()->id)->lockForUpdate()->first();
            $pause = DB::table('reporting_pauses')->where('active_user_id', $r->user()->id)->first();
            if ($r->input('action') === 'resume') {
                if ($pause) DB::table('reporting_pauses')->where('id', $pause->id)->update(['active_user_id' => null, 'ended_at' => now('UTC')]);
            } elseif (! $pause) DB::table('reporting_pauses')->insert(['point_of_sale_id' => $filter->shops[0], 'user_id' => $r->user()->id, 'active_user_id' => $r->user()->id, 'started_at' => now('UTC')]);
        }, 3);
        return back()->with('success', __('reporting.pause_recorded'));
    }

    public function workday(Request $r)
    {
        abort_unless($r->user()?->can('cash_session_manage'), 403);
        $filter = ReportFilter::fromRequest($r);
        $shops = PointOfSale::accessibleBy($r->user())->orderBy('name')->get();
        $pause = DB::table('reporting_pauses')->where('active_user_id', $r->user()->id)->first();
        $sessions = DB::table('cash_sessions')->where('user_id', $r->user()->id)->whereIn('point_of_sale_id', $filter->shops)->orderByDesc('id')->paginate(20)->withQueryString();
        return view('backend.reporting.workday', compact('filter', 'shops', 'pause', 'sessions'));
    }

    public function mailActivation(Request $r)
    {
        // Compatibility route now shares the settings workflow.
        return $this->summarySettingsUpdate($r);
    }

    private function authorizeSummarySettings(Request $r): void
    {
        $this->authorizeSummary($r);
        abort_unless($r->user()->hasRole('Admin') && $r->user()->hasAllPermissions(['daily_summary_close', 'user_view', 'point_of_sale_manage_all']), 403);
    }

    public function summarySettings(Request $r)
    {
        $this->authorizeSummarySettings($r);
        $approval = app(\App\Services\SummaryMailApproval::class);
        $mailEnabled = $approval->enabled();
        $recipients = $approval->recipients();
        $transportReady = $approval->transportReady();
        $mailPreview = $approval->preview($r->user());
        $mailPreview['pairs'] = array_values(array_filter($mailPreview['pairs'], fn ($p) => $p['user_id'] === $r->user()->id));
        return view('backend.reporting.summary-settings', compact('mailEnabled', 'recipients', 'transportReady', 'mailPreview'));
    }

    public function summarySettingsUpdate(Request $r)
    {
        $this->authorizeSummarySettings($r);
        $v = $r->validate(['enable' => 'required|boolean', 'recipients' => 'nullable|string|max:6000', 'consent' => 'exclude_unless:enable,1|required|accepted', 'approval_hash' => 'nullable|string|size:64']);
        $emails = preg_split('/[\s,;]+/', trim($v['recipients'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $emails = array_values(array_unique(array_map('strtolower', $emails)));
        abort_if(count($emails) > 20, 422, __('reporting.invalid_recipients'));
        foreach ($emails as $email) abort_unless(strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL), 422, __('reporting.invalid_recipients'));
        $approval = app(\App\Services\SummaryMailApproval::class);
        if ($v['enable']) {
            abort_unless($emails && $r->boolean('consent'), 422, __('reporting.mail_consent'));
            abort_unless($approval->transportReady(), 422, __('reporting.smtp_missing'));
        }
        DB::transaction(function () use ($r, $v, $emails, $approval) {
            DB::table('users')->where('id', $r->user()->id)->lockForUpdate()->first();
            if ($v['enable']) {
                $manifest = $approval->preview($r->user());
                abort_unless($manifest['pairs'] && hash_equals($manifest['hash'], $v['approval_hash'] ?? ''), 409, __('reporting.approval_changed'));
                $pairs = [];
                foreach ($manifest['pairs'] as $pair) if ($pair['user_id'] === $r->user()->id) { $pair['emails'] = $emails; unset($pair['email']); $pairs[] = $pair; }
                abort_unless($pairs, 422); $manifest['pairs'] = $pairs;
                $manifest['approved_by'] = $r->user()->id; $manifest['approved_at'] = now('UTC')->toIso8601String();
                DB::table('reporting_runtime')->updateOrInsert(['key' => 'mail_approval'], ['value' => json_encode($manifest, JSON_THROW_ON_ERROR)]);
            } else {
                DB::table('reporting_runtime')->where('key', 'mail_approval')->delete();
            }
            DB::table('reporting_runtime')->updateOrInsert(['key' => 'mail_recipients'], ['value' => json_encode($emails, JSON_THROW_ON_ERROR)]);
            DB::table('reporting_runtime')->updateOrInsert(['key' => 'mail_enabled'], ['value' => $v['enable'] ? '1' : '0']);
        }, 3);
        return back()->with('success', __('reporting.mail_setting_saved'));
    }
}
