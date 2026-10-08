<?php

namespace App\Services;

use App\Models\PointOfSale;
use App\Models\User;
use App\Support\ReportFilter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DailySummaryService
{
    public function eligible(User $user, int $shop): bool
    {
        return ! $user->is_suspended && $user->hasAllPermissions(['point_of_sale_access', 'daily_summary_view', 'daily_summary_receive', 'reports_summary', 'reports_sales', 'reports_inventory'])
            && PointOfSale::accessibleBy($user)->whereKey($shop)->exists();
    }

    public function payload(int $shop, string $day, string $cutoff): array
    {
        $f = ReportFilter::day($shop, $day, $cutoff);
        $reports = app(ReportingService::class);
        $heads = [];
        foreach ([
            'sales' => $f->limit(DB::table('orders')->whereIn('point_of_sale_id', [$shop])->where('currency_code', 'XAF')->where('sale_state', '<>', 'legacy'), 'created_at'),
            'returns' => $f->limit(DB::table('sale_corrections as sc')->join('orders as o', 'o.id', '=', 'sc.order_id')->where('o.point_of_sale_id', $shop)->where('o.currency_code', 'XAF')->select('sc.id'), 'sc.created_at'),
            'expenses' => $reports->expenseQuery($f)->select('e.id'),
            'cash_movements' => $f->limit(DB::table('cash_movements as cm')->join('cash_sessions as cs', 'cs.id', '=', 'cm.cash_session_id')->where('cs.point_of_sale_id', $shop)->select('cm.id'), 'cm.occurred_at'),
            'opened_sessions' => $f->limit(DB::table('cash_sessions')->where('point_of_sale_id', $shop), 'opened_at'),
            'closed_sessions' => $f->limit(DB::table('cash_sessions')->where('point_of_sale_id', $shop)->whereNotNull('closed_at'), 'closed_at'),
            'payments' => DB::table('payments')->where('point_of_sale_id', $shop)->where('currency_code', 'XAF')->where(function ($q) use ($f, $cutoff) {
                $q->where(function ($q) use ($f) { $f->limit($q->whereNotNull('receipt_snapshot'), 'occurred_at'); })
                    ->orWhere(function ($q) use ($f, $cutoff) {
                        $q->whereNull('receipt_snapshot')->whereNotNull('supplier_id')->where('occurred_at', '>=', $f->start->format('Y-m-d H:i:s'))
                            ->where('occurred_at', '<=', CarbonImmutable::parse($cutoff, 'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s'));
                    });
            }),
        ] as $key => $query) {
            $alias = match ($key) { 'returns' => 'sc.id', 'expenses' => 'e.id', 'cash_movements' => 'cm.id', default => 'id' };
            $ids = (clone $query)->orderBy($alias)->pluck($alias)->map(fn ($id) => (string) $id)->all();
            $heads[$key] = ['count' => count($ids), 'last_id' => $ids ? end($ids) : 0, 'ids' => $ids];
        }
        $open = DB::table('cash_sessions as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.point_of_sale_id', $shop)->where('s.opened_at', '<=', $cutoff)
            ->where(fn ($q) => $q->whereNull('s.closed_at')->orWhere('s.closed_at', '>', $cutoff))->orderBy('s.id')->get(['s.id', 'u.name', 's.opened_at'])->map(fn ($v) => (array) $v)->all();
        return ['summary' => $reports->summary($f), 'daily' => $reports->daily($f), 'peaks' => $reports->peaks($f), 'stock' => $reports->stockTotals($f), 'open_sessions' => $open, 'source_heads' => $heads];
    }

    public function generate(int $shop, string $day, string $requested, ?int $actor = null): object
    {
        if (! in_array($requested, ['manual', 'automatic'], true)) throw new \InvalidArgumentException('Invalid closure type.');
        return DB::transaction(function () use ($shop, $day, $requested, $actor) {
            $store = PointOfSale::active()->whereKey($shop)->lockForUpdate()->firstOrFail();
            $rows = DB::table('daily_summaries')->where('point_of_sale_id', $shop)->where('business_date', $day)->orderBy('version')->get();
            $initial = $rows->first();
            if ($requested === 'manual' && $initial) return $initial;
            $end = ReportFilter::day($shop, $day)->end->utc()->subSecond()->format('Y-m-d H:i:s');
            $cutoff = min(now('UTC')->format('Y-m-d H:i:s'), $end);
            $payload = $this->payload($shop, $day, $cutoff);
            $financial = $payload;
            unset($financial['stock']);
            $fingerprint = hash('sha256', json_encode($financial, JSON_THROW_ON_ERROR));
            if ($initial) {
                $latest = $rows->last();
                $previous = json_decode($latest->payload, true, 512, JSON_THROW_ON_ERROR);
                unset($previous['stock'], $previous['shop_label']);
                if (hash_equals(hash('sha256', json_encode($previous, JSON_THROW_ON_ERROR)), $fingerprint)) return $latest;
            }
            if ($initial && $rows->contains('closure_type', 'corrected')) {
                // The corrected version is immutable too. Later activity remains
                // visible in its own log and triggers an explicit admin notice.
                $corrected = $rows->firstWhere('closure_type', 'corrected');
                $this->queue($corrected, 'late_activity');
                return $corrected;
            }
            $type = $initial ? 'corrected' : $requested;
            $payload['shop_label'] = $store->name;
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
            // Compare only the deterministic data, without presentation metadata.
            $id = DB::table('daily_summaries')->insertGetId([
                'point_of_sale_id' => $shop, 'business_date' => $day, 'closure_type' => $type,
                'version' => $initial ? 2 : 1, 'cutoff_at' => $cutoff,
                'activity_id' => DB::table('reporting_activity')->where('point_of_sale_id', $shop)->max('id') ?? 0,
                'created_by' => $actor, 'payload_hash' => hash('sha256', $encoded),
                'payload' => $encoded, 'created_at' => now('UTC'),
            ]);
            $row = DB::table('daily_summaries')->find($id);
            $this->pdf($row);
            $row = DB::table('daily_summaries')->find($id);
            $this->queue($row, 'summary');
            return $row;
        }, 3);
    }

    private function queue(object $summary, string $kind): void
    {
        User::where('is_suspended', false)->orderBy('id')->chunkById(100, function ($users) use ($summary, $kind) {
            foreach ($users as $user) {
                if (! $this->eligible($user, $summary->point_of_sale_id) || ($kind === 'late_activity' && ! $user->hasRole('Admin'))) continue;
                DB::table('summary_deliveries')->insertOrIgnore([
                    'daily_summary_id' => $summary->id, 'user_id' => $user->id, 'kind' => $kind,
                    'state' => 'pending', 'attempts' => 0, 'next_attempt_at' => now('UTC'),
                ]);
            }
        });
    }

    public function runDue(): array
    {
        $local = CarbonImmutable::now('Africa/Douala');
        $activation = DB::table('reporting_runtime')->where('key', 'activated_at')->value('value');
        $first = CarbonImmutable::parse($activation, 'UTC')->setTimezone('Africa/Douala')->startOfDay();
        $last = $local->format('H:i') >= '23:55' ? $local->startOfDay() : $local->startOfDay()->subDay();
        // Bounded work per invocation; the persisted cursor continues long outages.
        $cursor = DB::table('reporting_runtime')->where('key', 'catchup_date')->value('value');
        $start = $cursor ? CarbonImmutable::parse($cursor, 'Africa/Douala') : $first;
        $result = ['generated_or_checked' => 0, 'through' => null];
        for ($d = $start, $n = 0; $d->lessThanOrEqualTo($last) && $n < 7; $d = $d->addDay(), $n++) {
            foreach (PointOfSale::active()->orderBy('id')->get() as $shop) {
                $this->generate($shop->id, $d->toDateString(), 'automatic');
                if ($d->toDateString() < $local->toDateString()) DB::table('reporting_runtime')->updateOrInsert(['key' => 'reconciled:'.$shop->id.':'.$d->toDateString()], ['value' => now('UTC')->format('Y-m-d H:i:s')]);
                $result['generated_or_checked']++;
            }
            $result['through'] = $d->toDateString();
            DB::table('reporting_runtime')->updateOrInsert(['key' => 'catchup_date'], ['value' => $d->addDay()->toDateString()]);
        }
        // Recheck yesterday after midnight for activity between 23:55 and 24:00;
        // at 23:55 also compare today's manual version even if the cursor advanced.
        if ($last->greaterThanOrEqualTo($first)) {
            foreach (PointOfSale::active()->orderBy('id')->get() as $shop) {
                $key = 'reconciled:'.$shop->id.':'.$last->toDateString();
                if ($last->toDateString() < $local->toDateString() && DB::table('reporting_runtime')->where('key', $key)->exists()) continue;
                $this->generate($shop->id, $last->toDateString(), 'automatic');
                if ($last->toDateString() < $local->toDateString()) DB::table('reporting_runtime')->updateOrInsert(['key' => $key], ['value' => now('UTC')->format('Y-m-d H:i:s')]);
            }
        }
        DB::table('reporting_runtime')->updateOrInsert(['key' => 'scheduler_last_run'], ['value' => now('UTC')->format('Y-m-d H:i:s')]);
        return $result;
    }

    public function lateQuery(object $summary)
    {
        $f = ReportFilter::day($summary->point_of_sale_id, $summary->business_date);
        $heads = json_decode($summary->payload, true, 512, JSON_THROW_ON_ERROR)['source_heads'];
        $q = app(ReportingService::class)->events($f)->where(function ($q) use ($summary, $heads) {
            $q->where('occurred_at', '>', $summary->cutoff_at);
            foreach (['sale' => 'sales', 'return' => 'returns'] as $kind => $key) if (isset($heads[$key]['ids'])) $q->orWhere(fn ($q) => $q->where('event_kind', $kind)->whereNotIn('source_id', $heads[$key]['ids']));
        })
            ->selectRaw('occurred_at, event_kind, source_id, SUM(net_sales) as net_sales')->groupBy('occurred_at', 'event_kind', 'source_id');
        $expenses = app(ReportingService::class)->expenseQuery($f)->where(function ($q) use ($summary, $heads) { $q->where('e.occurred_at', '>', $summary->cutoff_at); if (isset($heads['expenses']['ids'])) $q->orWhereNotIn('e.id', $heads['expenses']['ids']); })
            ->selectRaw("e.occurred_at, 'expense' as event_kind, e.id as source_id, -e.amount as net_sales");
        $payments = DB::table('payments')->where('point_of_sale_id', $summary->point_of_sale_id)->where('currency_code', 'XAF')->whereNotNull('receipt_snapshot');
        $f->limit($payments, 'occurred_at');
        $this->afterCutoff($payments, 'occurred_at', 'id', $summary, $heads['payments'] ?? []);
        $payments->selectRaw("occurred_at, 'payment' as event_kind, id as source_id, CASE WHEN direction = 'incoming' THEN net_amount ELSE -net_amount END as net_sales");
        $movements = $f->limit(DB::table('cash_movements as cm')->join('cash_sessions as cs', 'cs.id', '=', 'cm.cash_session_id')->where('cs.point_of_sale_id', $summary->point_of_sale_id), 'cm.occurred_at')
            ->selectRaw("cm.occurred_at, 'cash_movement' as event_kind, cm.id as source_id, CASE WHEN cm.direction = 'in' THEN cm.amount ELSE -cm.amount END as net_sales");
        $this->afterCutoff($movements, 'cm.occurred_at', 'cm.id', $summary, $heads['cash_movements'] ?? []);
        $sessions = $f->limit(DB::table('cash_sessions')->where('point_of_sale_id', $summary->point_of_sale_id)->whereNotNull('closed_at'), 'closed_at')
            ->selectRaw("closed_at as occurred_at, 'cash_session' as event_kind, id as source_id, COALESCE(difference,0) as net_sales");
        $this->afterCutoff($sessions, 'closed_at', 'id', $summary, $heads['closed_sessions'] ?? []);
        $openings = $f->limit(DB::table('cash_sessions')->where('point_of_sale_id', $summary->point_of_sale_id), 'opened_at')
            ->selectRaw("opened_at as occurred_at, 'cash_session' as event_kind, id as source_id, opening_amount as net_sales");
        $this->afterCutoff($openings, 'opened_at', 'id', $summary, $heads['opened_sessions'] ?? []);
        $supplier = DB::table('payments')->where('point_of_sale_id', $summary->point_of_sale_id)->where('currency_code', 'XAF')->whereNull('receipt_snapshot')->whereNotNull('supplier_id')
            ->where('occurred_at', '>=', $f->start->format('Y-m-d H:i:s'))->where('occurred_at', '<', $f->end->format('Y-m-d H:i:s'))
            ->selectRaw("DATE_SUB(occurred_at, INTERVAL 1 HOUR) as occurred_at, 'payment' as event_kind, id as source_id, CASE WHEN direction = 'incoming' THEN net_amount ELSE -net_amount END as net_sales");
        $this->afterCutoff($supplier, 'occurred_at', 'id', $summary, $heads['payments'] ?? [], true);
        return DB::query()->fromSub($q->unionAll($expenses)->unionAll($payments)->unionAll($movements)->unionAll($sessions)->unionAll($openings)->unionAll($supplier), 'late')->orderBy('occurred_at')->orderBy('source_id');
    }

    private function afterCutoff($q, string $time, string $id, object $summary, array $head, bool $local = false): void
    {
        $cutoff = $local ? CarbonImmutable::parse($summary->cutoff_at, 'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s') : $summary->cutoff_at;
        $q->where(function ($q) use ($time, $id, $cutoff, $head) {
            $q->where($time, '>', $cutoff);
            if (isset($head['ids'])) $q->orWhereNotIn($id, $head['ids']);
        });
    }

    public function pdf(object $row): string
    {
        return DB::transaction(function () use ($row) {
            $fresh = DB::table('daily_summaries')->where('id', $row->id)->lockForUpdate()->first();
            if (! $fresh) throw new \RuntimeException('Summary not found.');
            return $this->preservePdf($fresh);
        }, 3);
    }

    private function preservePdf(object $row): string
    {
        if (! hash_equals($row->payload_hash, hash('sha256', $row->payload))) throw new \RuntimeException('Summary integrity verification failed.');
        $path = 'reporting/'.DB::connection()->getDatabaseName().'/summaries/'.$row->id.'-'.$row->payload_hash.'.pdf';
        $disk = Storage::disk('local');
        if (! empty($row->pdf_sha256)) {
            if ($row->pdf_path !== $path || ! $disk->exists($path)) throw new \RuntimeException('Summary PDF evidence is missing.');
            $bytes = $disk->get($path);
            if (! hash_equals($row->pdf_sha256, hash('sha256', $bytes))) throw new \RuntimeException('Summary PDF integrity verification failed.');
            return $bytes;
        }
        $data = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
        $bytes = \Barryvdh\DomPDF\Facade\Pdf::loadView('backend.reporting.summary-pdf', compact('row', 'data'))->setPaper('a4')->output();
        if (! $disk->put($path, $bytes)) throw new \RuntimeException('Cannot preserve summary PDF.');
        DB::table('daily_summaries')->where('id', $row->id)->whereNull('pdf_sha256')->update(['pdf_path' => $path, 'pdf_sha256' => hash('sha256', $bytes)]);
        return $bytes;
    }
}
