<?php

namespace App\Console\Commands;

use App\Services\SummaryDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ReportDeliverCommand extends Command
{
    protected $signature = 'reports:deliver {--limit=20}';
    protected $description = 'Deliver authorized daily summaries with tracked retries.';

    public function handle(SummaryDeliveryService $service): int
    {
        if (Schema::hasTable('summary_deliveries')) $this->info(json_encode($service->run((int) $this->option('limit')), JSON_THROW_ON_ERROR));
        return self::SUCCESS;
    }
}
