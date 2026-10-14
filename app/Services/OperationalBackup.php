<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema,Storage};
final class OperationalBackup
{
 public function ready():bool {return Schema::hasTable('backup_runs');}
 public function enabled():bool {return(bool)config('system.backup_enabled',config('backups.enabled'));}
 public function cloudEnabled():bool {return(bool)config('system.backup_cloud_enabled',false);}
 public function cloudReady():bool {
  $disk=(array)config('filesystems.disks.backup_s3',[]);
  return class_exists(\Aws\S3\S3Client::class)&&!empty($disk['key'])&&!empty($disk['secret'])
   &&(bool)config('system.backup_cloud_bucket',$disk['bucket']??null)&&strlen((string)config('backups.password'))>=16;
 }
 private function cloudDisk() {
  config(['filesystems.disks.backup_s3.bucket'=>config('system.backup_cloud_bucket',config('filesystems.disks.backup_s3.bucket')),
   'filesystems.disks.backup_s3.endpoint'=>config('system.backup_cloud_endpoint',config('filesystems.disks.backup_s3.endpoint'))]);
  Storage::forgetDisk('backup_s3');
  return Storage::disk(config('backups.cloud_disk'));
 }
 public function due():bool {
  if(!$this->ready()||!$this->enabled())return false;
  $last=DB::table('backup_runs')->where('state','verified')->latest('completed_at')->first();
  if($last&&\Carbon\CarbonImmutable::parse($last->started_at,'UTC')->addHours(23)->isFuture())return false;
  $failed=DB::table('backup_runs')->where('state','failed')->latest('id')->first();
  return!$failed||\Carbon\CarbonImmutable::parse($failed->completed_at,'UTC')->addMinutes(15)->isPast();
 }
 public function run(bool$onlyIfDue=false):?object {
  if(!$this->ready())throw new \RuntimeException('backup_migration_required');
  $db=DB::connection();$name=$db->getDatabaseName();$lock='qpos_backup_'.substr(hash('sha256',$name),0,48);
  if((int)$db->selectOne('SELECT GET_LOCK(?,0) AS acquired',[$lock])->acquired!==1)return null;
  $id=null;$sql=null;
  try{
   if($onlyIfDue&&!$this->due())return null;
   $stale=DB::table('backup_runs')->where('state','running')->where('started_at','<',now('UTC')->subMinutes(30))->limit(100)->get();
   foreach($stale as$r)DB::transaction(function()use($r){DB::table('backup_runs')->where('id',$r->id)->update(['state'=>'failed','last_error'=>'backup_interrupted','completed_at'=>now('UTC'),'updated_at'=>now('UTC')]);app(NotificationSignals::class)->backupFailed(DB::table('backup_runs')->find($r->id));});
   $id=DB::table('backup_runs')->insertGetId(['database_name'=>$name,'state'=>'running','started_at'=>now('UTC'),'created_at'=>now('UTC'),'updated_at'=>now('UTC')]);
   $archive=app(BackupArchive::class);$root=$archive->root();$sql=$root.'/database-'.$id.'-'.bin2hex(random_bytes(8)).'.sql';
   $snapshot=app(DatabaseBackup::class)->dump($sql);
   $result=$archive->create($id,$sql,$snapshot);
   DB::table('backup_runs')->where('id',$id)->update($result+['state'=>'verified','completed_at'=>now('UTC'),'updated_at'=>now('UTC')]);
   $run=DB::table('backup_runs')->find($id);
   if($this->cloudEnabled())$this->upload($run);
   try{$this->retain();}catch(\Throwable){DB::table('backup_runs')->where('id',$id)->update(['last_error'=>'retention_failed','updated_at'=>now('UTC')]);}
   DB::table('reporting_runtime')->updateOrInsert(['key'=>'backup_worker_last_run'],['value'=>now('UTC')->toIso8601String()]);
   return DB::table('backup_runs')->find($id);
  }catch(\Throwable$error){
   if($id)DB::transaction(function()use($id,$error){
    $message=$error->getMessage();$code=preg_match('/^[a-z0-9_]{1,80}$/',$message)?$message:'backup_generation_failed';
    DB::table('backup_runs')->where('id',$id)->update(['state'=>'failed','last_error'=>$code,'completed_at'=>now('UTC'),'updated_at'=>now('UTC')]);
    app(NotificationSignals::class)->backupFailed(DB::table('backup_runs')->find($id));
   },3);
   return$id?DB::table('backup_runs')->find($id):null;
  }finally{
   if($sql&&is_file($sql)&&!is_link($sql))unlink($sql);
   $db->selectOne('SELECT RELEASE_LOCK(?) AS released',[$lock]);
  }
 }
 public function upload(object$run):void {
  if(!$this->cloudEnabled())return;
  if(!$this->cloudReady()||!$run->encrypted){DB::table('backup_runs')->where('id',$run->id)->update(['cloud_state'=>'unconfigured','updated_at'=>now('UTC')]);return;}
  $archive=app(BackupArchive::class);$path=$archive->path($run->archive_path);
  $key=trim(config('system.backup_cloud_prefix',config('backups.cloud_prefix')),'/').'/'.$run->database_name.'/'.$run->archive_path;
  try{
   $archive->verify($path,$run->archive_sha256);$disk=$this->cloudDisk();$in=fopen($path,'rb');
   try{$ok=$disk->put($key,$in,['visibility'=>'private']);}finally{fclose($in);}
   if(!$ok)throw new \RuntimeException('cloud_upload_failed');
   $remote=$disk->readStream($key);if(!$remote)throw new \RuntimeException('cloud_readback_failed');
   $hash=hash_init('sha256');hash_update_stream($hash,$remote);fclose($remote);
   if(!hash_equals($run->archive_sha256,hash_final($hash)))throw new \RuntimeException('cloud_integrity_failed');
   DB::table('backup_runs')->where('id',$run->id)->update(['cloud_state'=>'verified','cloud_key'=>$key,'updated_at'=>now('UTC')]);
  }catch(\Throwable){
   DB::transaction(function()use($run){DB::table('backup_runs')->where('id',$run->id)->update(['cloud_state'=>'failed','last_error'=>'cloud_backup_failed','updated_at'=>now('UTC')]);
    app(OperationalNotifications::class)->record('backup_failed','cloud:'.$run->id.':'.$run->attempts,null,$run->id,['cloud'=>true]);},3);
  }
 }
 public function retain():int {
  if(!(bool)config('system.backup_retention_enabled',false))return 0;
  $protect=DB::table('backup_runs')->where('state','verified')->latest('completed_at')->limit(2)->pluck('id')->all();
  $restored=DB::table('backup_runs')->whereNotNull('restore_verified_at')->latest('restore_verified_at')->value('id');if($restored)$protect[]=$restored;
  $rows=DB::table('backup_runs')->where('state','verified')->whereNotIn('id',$protect)->where('completed_at','<',now('UTC')->subDays(max(7,(int)config('system.backup_retention_days',30))))->orderBy('id')->limit(100)->get();
  $count=0;foreach($rows as$r){
   if($this->cloudEnabled()&&$r->cloud_state!=='verified')continue;
   $path=app(BackupArchive::class)->path($r->archive_path);
   if(is_file($path)&&!unlink($path))continue;
   DB::table('backup_runs')->where('id',$r->id)->update(['state'=>'retained_metadata','updated_at'=>now('UTC')]);$count++;
  }
  return$count;
 }
}
