<?php
namespace App\Jobs;
use App\Services\OperationalBackup;
use App\Models\User;
use App\Support\RoleAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
final class RunOperationalBackup implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public int$tries=1;public int$timeout=1200;
 public function __construct(public ?int$requestedBy=null,public bool$onlyIfDue=true){}
 public function handle(OperationalBackup$service):void {
  if($this->requestedBy){$u=User::find($this->requestedBy);if(!$u||$u->is_suspended||!RoleAssignment::globalManager($u)||!$u->can('system_monitor'))return;}
  $service->run($this->onlyIfDue);
 }
}
