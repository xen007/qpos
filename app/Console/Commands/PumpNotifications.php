<?php
namespace App\Console\Commands;

use App\Services\{NotificationScan,OperationalNotifications};
use Illuminate\Console\Command;

final class PumpNotifications extends Command
{
    protected $signature='notifications:pump {--limit=500} {--no-scan}';
    protected $description='Scan bounded operational alerts and enqueue committed outbox events';
    public function handle(NotificationScan $scan,OperationalNotifications $notifications): int
    {
        if(!$notifications->ready()){$this->error('Notification migration required.');return self::FAILURE;}
        $limit=(int)$this->option('limit');$counts=$this->option('no-scan')?[]:$scan->run($limit);
        $counts['queued']=$notifications->pump($limit);$this->info(json_encode($counts,JSON_THROW_ON_ERROR));return self::SUCCESS;
    }
}
