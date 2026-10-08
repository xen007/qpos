<?php

namespace App\Providers;

use App\Models\CashSession;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStockAllocation;
use App\Models\Payment;
use App\Services\ReportEvidence;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        OrderProduct::created(fn ($m) => app(ReportEvidence::class)->line($m));
        OrderStockAllocation::created(fn ($m) => app(ReportEvidence::class)->allocation($m));
        Order::created(function ($m) {
            if ($m->currency_code === 'XAF' && $m->sale_state !== 'legacy') app(ReportEvidence::class)->activity('sale', $m->id, $m->point_of_sale_id);
        });
        Payment::created(function ($m) {
            if ($m->point_of_sale_id && $m->currency_code === 'XAF') app(ReportEvidence::class)->activity('payment', $m->id, $m->point_of_sale_id);
        });
        CashSession::saved(function ($m) {
            if ($m->wasRecentlyCreated || $m->wasChanged('closed_at')) app(ReportEvidence::class)->activity('cash_session', $m->id, $m->point_of_sale_id);
        });
        // Corrections and expenses use the query builder in Phase 4. Their IDs
        // are discovered by the summary service without altering that workflow.
    }
}
