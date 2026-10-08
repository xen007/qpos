<?php

namespace App\Console\Commands;

use App\Services\DailySummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ReportDailyCommand extends Command
{
    protected $signature = 'reports:daily';
    protected $description = 'Generate due shop summaries and reconcile early daily closures.';

    public function handle(DailySummaryService $service): int
    {
        if (! Schema::hasTable('daily_summaries')) return self::SUCCESS;
        $lock = Cache::lock('qpos-report-daily', 600);
        if (! $lock->get()) return self::SUCCESS;
        try { $this->info(json_encode($service->runDue(), JSON_THROW_ON_ERROR)); }
        finally { $lock->release(); }
        return self::SUCCESS;
    }
}
