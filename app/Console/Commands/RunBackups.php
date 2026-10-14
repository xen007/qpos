<?php
namespace App\Console\Commands;
use App\Jobs\RunOperationalBackup;
use App\Services\OperationalBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
final class RunBackups extends Command {
 protected$signature='backups:run {--if-due} {--enqueue}';
 protected$description='Create a private verified operational backup or enqueue daily catch-up';
 public function handle(OperationalBackup$s):int {
  if(!$s->ready()){$this->error('Backup migration required.');return self::FAILURE;}
  DB::table('reporting_runtime')->updateOrInsert(['key'=>'backup_scheduler_last_run'],['value'=>now('UTC')->toIso8601String()]);
  if($this->option('if-due')&&!$s->due()){$this->info('Backup not due.');return self::SUCCESS;}
  if($this->option('enqueue')){RunOperationalBackup::dispatch(null,(bool)$this->option('if-due'))->onConnection('backup_database')->onQueue('qpos-backups')->afterCommit();$this->info('Backup queued.');return self::SUCCESS;}
  $run=$s->run((bool)$this->option('if-due'));if(!$run){$this->info('Backup already running.');return self::SUCCESS;}
  $this->info('Backup #'.$run->id.' '.$run->state.' / cloud '.$run->cloud_state);
  return$run->state==='verified'?self::SUCCESS:self::FAILURE;
 }
}
