<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
final class CriticalPurchaseAlerts extends Command {
    protected $signature='qpos:purchase-alerts-critical {--limit=200}';
    protected $description='Check native consumption coverage for configured authorised recipients.';
    public function handle(): int {
        $result=app(\App\Services\ReplenishmentCriticalScan::class)->run((int)$this->option('limit'));
        $this->line(json_encode($result,JSON_THROW_ON_ERROR)); return isset($result['unavailable'])?self::FAILURE:self::SUCCESS;
    }
}
