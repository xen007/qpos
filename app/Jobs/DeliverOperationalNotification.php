<?php
namespace App\Jobs;

use App\Services\OperationalNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue,SerializesModels};

final class DeliverOperationalNotification implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
    public int $tries=5;
    public int $timeout=60;
    public function __construct(public int $eventId) {}
    public function backoff(): array { return [60,120,300,900]; }
    public function handle(OperationalNotifications $notifications): void { $notifications->deliver($this->eventId); }
}
