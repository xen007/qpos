<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
final class BackupRestoration {
 public function verify(int$id):array {
  $row=DB::table('backup_runs')->find($id);
  if(!$row||$row->state!=='verified')throw new \RuntimeException('verified_backup_required');
  $start=microtime(true);$archive=app(BackupArchive::class);
  $target='qpos_restore_'.gmdate('YmdHis').'_'.bin2hex(random_bytes(4));
  $destination=$archive->root().'/restores/'.$target;
  $manifest=$archive->verify($archive->path($row->archive_path),$row->archive_sha256,$destination);
  app(DatabaseBackup::class)->restore($destination.'/database.sql',$target);
  $config=DB::connection()->getConfig();config(['database.connections.backup_probe'=>array_merge($config,['database'=>$target])]);DB::purge('backup_probe');
  $probe=DB::connection('backup_probe');if($probe->selectOne('SELECT DATABASE() AS db')->db!==$target)throw new \RuntimeException('restore_database_guard_failed');
  $base=[];$views=[];foreach($probe->select('SHOW FULL TABLES')as$table){$a=array_values((array)$table);if($a[1]==='BASE TABLE')$base[]=$a[0];else$views[]=$a[0];}
  sort($base);$expected=array_keys($manifest['tables']);sort($expected);sort($views);
  if($base!==$expected||$views!==$manifest['views'])throw new \RuntimeException('restored_schema_mismatch');
  foreach($manifest['tables']as$name=>$count){
   if(!preg_match('/^[A-Za-z0-9_]+$/',$name)||$probe->table($name)->count()!==(int)$count)throw new \RuntimeException('restored_rows_mismatch');
  }
  $seconds=round(microtime(true)-$start,3);
  DB::table('backup_runs')->where('id',$id)->update(['restore_database'=>$target,'restore_seconds'=>$seconds,'restore_verified_at'=>now('UTC'),'updated_at'=>now('UTC')]);
  return['database'=>$target,'directory'=>$destination,'seconds'=>$seconds,'tables'=>count($base),'files'=>count($manifest['files']),'source_unchanged'=>true];
 }
}
