<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
final class WeeklyPurchaseSuggestions extends Command {
    protected $signature='qpos:purchase-suggestions-weekly {--limit=5}';
    protected $description='Prepare due weekly purchase drafts, never approve or send them.';
    public function handle(): int {
        $result=app(\App\Services\ReplenishmentPlanner::class)->schedule('weekly',(int)$this->option('limit'));
        $this->line(json_encode($result,JSON_THROW_ON_ERROR)); return isset($result['unavailable'])?self::FAILURE:self::SUCCESS;
    }
}
