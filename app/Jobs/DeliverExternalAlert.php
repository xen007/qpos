<?php
namespace App\Jobs;
use App\Services\ExternalAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
final class DeliverExternalAlert implements ShouldQueue {
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
    public int $tries=1;public int $timeout=60;
    public function __construct(public int $deliveryId){}
    public function handle(ExternalAlertService $service): void{$service->deliver($this->deliveryId);}
}
