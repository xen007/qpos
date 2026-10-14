<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema};
use Carbon\CarbonImmutable;
final class OperationalStatus {
 public function snapshot():array {
  $clock=CarbonImmutable::now('UTC');$runtime=DB::table('reporting_runtime')->pluck('value','key');
  $heartbeats=[];foreach(['notification_scheduler_last_run','notification_worker_last_run','scheduler_last_run','backup_scheduler_last_run','backup_worker_last_run']as$key){
   $date=isset($runtime[$key])?CarbonImmutable::parse($runtime[$key],'UTC'):null;
   $heartbeats[$key]=['at'=>$date?->setTimezone('Africa/Douala')->format('Y-m-d H:i:s'),'age_seconds'=>$date?max(0,$clock->timestamp-$date->timestamp):null];
  }
  $backup=app(OperationalBackup::class);
  $last=$backup->ready()?DB::table('backup_runs')->where('state','verified')->latest('completed_at')->first():null;
  $restored=$backup->ready()?DB::table('backup_runs')->whereNotNull('restore_verified_at')->latest('restore_verified_at')->first():null;
  $age=$last?max(0,$clock->timestamp-CarbonImmutable::parse($last->started_at,'UTC')->timestamp):null;
  $jobs=Schema::hasTable('notification_jobs')?DB::table('notification_jobs')->count():null;
  $oldest=Schema::hasTable('notification_jobs')?DB::table('notification_jobs')->min('created_at'):null;
  return ['heartbeats'=>$heartbeats,'backup'=>$last,'restored'=>$restored,'backup_age_seconds'=>$age,'rpo_ok'=>$age!==null&&$age<=86400,
   'rto_ok'=>$restored&&$restored->restore_seconds!==null?(float)$restored->restore_seconds<=28800:null,
   'pending_jobs'=>$jobs,'oldest_job_age_seconds'=>$oldest?max(0,$clock->timestamp-(int)$oldest):null,
   'external_states'=>Schema::hasTable('notification_outbound')?DB::table('notification_outbound')->selectRaw('state,COUNT(*) AS total')->groupBy('state')->pluck('total','state'):collect(),
   'summary_states'=>DB::table('summary_deliveries')->selectRaw('state,COUNT(*) AS total')->groupBy('state')->pluck('total','state'),
   'event_states'=>Schema::hasTable('notification_events')?DB::table('notification_events')->selectRaw('state,COUNT(*) AS total')->groupBy('state')->pluck('total','state'):collect(),
   'free_bytes'=>disk_free_space(storage_path())?:null,'daily_mail_enabled'=>app(SummaryMailApproval::class)->enabled(),
   'smtp_ready'=>app(SummaryMailApproval::class)->transportReady(),'local_backup_enabled'=>$backup->enabled(),
   'cloud_enabled'=>$backup->cloudEnabled(),'cloud_ready'=>$backup->cloudReady(),
  ];
 }
}
