<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
final class DatabaseBackup {
 public function dump(string$path):array {
  $db=DB::connection();if($db->getDriverName()!=='mysql')throw new \RuntimeException('mysql_required');
  $tables=[];$views=[];foreach($db->select('SHOW FULL TABLES')as$row){
   $a=array_values((array)$row);if(!preg_match('/^[A-Za-z0-9_]+$/',$a[0]))throw new \RuntimeException('unsupported_table_name');
   if($a[1]==='BASE TABLE')$tables[$a[0]]=0;else$views[]=$a[0];
  }
  if($db->table('information_schema.TABLES')->where('TABLE_SCHEMA',$db->getDatabaseName())->where('TABLE_TYPE','BASE TABLE')->where('ENGINE','<>','InnoDB')->exists())throw new \RuntimeException('non_transactional_tables');
  $cfg=$db->getConfig();$snapshot=now('UTC')->toIso8601String();
  $this->process(config('backups.dump_binary'),['--single-transaction','--quick','--routines','--events','--triggers','--hex-blob','--skip-comments','--skip-extended-insert','--no-autocommit','--complete-insert','--default-character-set=utf8mb4','--result-file='.$path,$cfg['database']],$cfg,'database_dump_failed');
  $in=fopen($path,'rb');if(!$in)throw new \RuntimeException('dump_missing');
  while(($line=fgets($in))!==false)if(preg_match('/^INSERT INTO `([^`]+)`/',$line,$m)&&array_key_exists($m[1],$tables))$tables[$m[1]]++;
  fclose($in);ksort($tables);sort($views);
  return ['tables'=>$tables,'views'=>$views,'snapshot_at'=>$snapshot,'database'=>$cfg['database'],'sql_sha256'=>hash_file('sha256',$path)];
 }
 public function restore(string$sql,string$target):void {
  $cfg=DB::connection()->getConfig();
  if(!preg_match('/^qpos_restore_[0-9]{14}_[a-f0-9]{8}$/',$target)||$target===$cfg['database'])throw new \RuntimeException('restore_target_refused');
  if(DB::table('information_schema.SCHEMATA')->where('SCHEMA_NAME',$target)->exists())throw new \RuntimeException('restore_target_exists');
  DB::statement('CREATE DATABASE `'.$target.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  $in=fopen($sql,'rb');if(!$in)throw new \RuntimeException('restore_sql_missing');
  try{$this->process(config('backups.mysql_binary'),['--default-character-set=utf8mb4',$target],$cfg,'database_restore_failed',$in);}finally{fclose($in);}
 }
 private function process(string$binary,array$args,array$cfg,string$code,$input=null):void {
  if(!is_file($binary))throw new \RuntimeException('mysql_tool_missing');
  $p=new Process(array_merge([$binary,'--host='.$cfg['host'],'--port='.($cfg['port']??3306),'--user='.$cfg['username']],$args),base_path(),['MYSQL_PWD'=>(string)$cfg['password']]);
  $p->setTimeout(600);if($input)$p->setInput($input);
  try{$ok=$p->run()===0;}catch(\Throwable){$ok=false;}
  if(!$ok)throw new \RuntimeException($code);
 }
}
