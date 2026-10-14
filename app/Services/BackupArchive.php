<?php
namespace App\Services;
use ZipArchive;
final class BackupArchive {
 public function root():string {
  $path=(string)config('backups.root');if(!is_dir($path)&&!mkdir($path,0700,true))throw new \RuntimeException('backup_directory_failed');
  $base=realpath(storage_path('app/backups'));$root=realpath($path);
  if(!$base||!$root||!str_starts_with(strtolower(str_replace('\\','/',$root)),strtolower(str_replace('\\','/',$base)).'/'))throw new \RuntimeException('backup_directory_unsafe');
  file_put_contents($root.'/.htaccess',"Require all denied\n");return$root;
 }
 public function path(string$relative):string {
  if(!preg_match('/^backup-[0-9]+-[a-f0-9]{16}\.zip$/',$relative))throw new \RuntimeException('backup_path_refused');
  $p=$this->root().'/'.$relative;if(is_link($p))throw new \RuntimeException('backup_symlink_refused');return$p;
 }
 public function create(int$id,string$sql,array$database):array {
  $password=config('backups.password');$encrypted=is_string($password)&&strlen($password)>=16;
  if($password&&!$encrypted)throw new \RuntimeException('backup_password_too_short');
  $name='backup-'.$id.'-'.bin2hex(random_bytes(8)).'.zip';$path=$this->path($name);
  $zip=new ZipArchive;if($zip->open($path,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new \RuntimeException('archive_create_failed');
  $files=['database.sql'=>hash_file('sha256',$sql)];$zip->addFile($sql,'database.sql');
  $roots=['business/public'=>(string)config('filesystems.disks.public.root'),'business/media'=>public_path('media'),'business/logo'=>public_path('assets/images/logo'),'business/reporting'=>storage_path('app/reporting')];
  foreach($roots as$prefix=>$root){
   if(!is_dir($root)||is_link($root))continue;$resolved=realpath($root);
   $entries=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resolved,\FilesystemIterator::SKIP_DOTS));
   foreach($entries as$file){
    if(!$file->isFile()||$file->isLink())continue;$nameInZip=$prefix.'/'.str_replace('\\','/',substr($file->getPathname(),strlen($resolved)+1));
    if(!$this->safeName($nameInZip))throw new \RuntimeException('business_path_unsafe');
    $files[$nameInZip]=hash_file('sha256',$file->getPathname());
    if(!$zip->addFile($file->getPathname(),$nameInZip))throw new \RuntimeException('media_archive_failed');
   }
  }
  $manifest=$database+['format'=>'qpos-operational-v1','files'=>$files,'environment_secrets_included'=>false,'app_url'=>config('app.url'),'business_timezone'=>'Africa/Douala','php_version'=>PHP_VERSION,'laravel_version'=>app()->version(),'composer_lock_sha256'=>hash_file('sha256',base_path('composer.lock')),'required_secrets_separate'=>['APP_KEY','database credentials','BACKUP_ARCHIVE_PASSWORD when encrypted','provider credentials']];
  $zip->addFromString('manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
  if($encrypted)for($i=0;$i<$zip->numFiles;$i++)if(!$zip->setEncryptionIndex($i,ZipArchive::EM_AES_256,$password))throw new \RuntimeException('archive_encryption_failed');
  if(!$zip->close())throw new \RuntimeException('archive_write_failed');
  $this->verify($path,hash_file('sha256',$path));
  return ['archive_path'=>$name,'archive_sha256'=>hash_file('sha256',$path),'archive_bytes'=>filesize($path),'encrypted'=>$encrypted];
 }
 private function safeName(string$name):bool {
  if($name===''||str_contains($name,'\\')||str_contains($name,':')||str_starts_with($name,'/')||preg_match('/[\x00-\x1f]/',$name))return false;
  foreach(explode('/',$name)as$part){
   if($part===''||in_array($part,['.','..'],true)||preg_match('/[ .]$/',$part)||preg_match('/^(con|prn|aux|nul|com[1-9]|lpt[1-9])(\\..*)?$/i',$part))return false;
  }
  return true;
 }
 public function verify(string$path,string$hash,?string$extract=null):array {
  if(!is_file($path)||is_link($path)||!hash_equals($hash,hash_file('sha256',$path)))throw new \RuntimeException('archive_integrity_failed');
  $zip=new ZipArchive;if($zip->open($path)!==true)throw new \RuntimeException('archive_open_failed');
  try{
   if(config('backups.password'))$zip->setPassword(config('backups.password'));
   $bytes=$zip->getFromName('manifest.json');if($bytes===false)throw new \RuntimeException('manifest_unreadable');
   $m=json_decode($bytes,true,512,JSON_THROW_ON_ERROR);
   if(($m['format']??'')!=='qpos-operational-v1'||empty($m['files']['database.sql']))throw new \RuntimeException('manifest_invalid');
   $total=0;if($zip->numFiles>(int)config('backups.max_entries'))throw new \RuntimeException('archive_limit_exceeded');
   $names=[];$aliases=[];for($i=0;$i<$zip->numFiles;$i++){
    $stat=$zip->statIndex($i);$name=$stat['name'];$total+=$stat['size'];$opsys=0;$attr=0;$zip->getExternalAttributesIndex($i,$opsys,$attr);
    if(!$this->safeName($name)||(($attr>>16)&0170000)===0120000||isset($names[$name])||isset($aliases[mb_strtolower($name)]))throw new \RuntimeException('archive_path_unsafe');
    if($total>(int)config('backups.max_unpacked_bytes'))throw new \RuntimeException('archive_limit_exceeded');
    $names[$name]=true;$aliases[mb_strtolower($name)]=true;
   }
   if(array_diff(array_keys($m['files']),array_keys($names))||count($names)!==count($m['files'])+1)throw new \RuntimeException('manifest_entries_mismatch');
   foreach($m['files']as$name=>$expected){
    if(!is_string($expected)||!preg_match('/^[a-f0-9]{64}$/',$expected))throw new \RuntimeException('manifest_hash_invalid');
    $stream=$zip->getStream($name);if(!$stream)throw new \RuntimeException('archive_entry_unreadable');$digest=hash_init('sha256');hash_update_stream($digest,$stream);fclose($stream);
    if(!hash_equals($expected,hash_final($digest)))throw new \RuntimeException('archive_entry_corrupt');
   }
   if($extract){
    $restoreRoot=$this->root().'/restores';if(!is_dir($restoreRoot))mkdir($restoreRoot,0700,true);
    $normal=strtolower(str_replace('\\','/',$extract));$guard=strtolower(str_replace('\\','/',realpath($restoreRoot))).'/';
    if($normal!==$guard.strtolower(basename($extract))||!preg_match('/^[a-zA-Z0-9_]+$/',basename($extract))||file_exists($extract))throw new \RuntimeException('restore_directory_refused');
    mkdir($extract,0700,true);if(!$zip->extractTo($extract))throw new \RuntimeException('archive_extract_failed');
    $resolved=strtolower(str_replace('\\','/',realpath($extract))).'/';
    foreach($m['files']as$name=>$expected){
     $file=$extract.'/'.$name;$actual=realpath($file);
     if(!$actual||is_link($file)||!str_starts_with(strtolower(str_replace('\\','/',$actual)),$resolved)||!hash_equals($expected,hash_file('sha256',$file)))throw new \RuntimeException('restored_file_corrupt');
    }
   }
   return$m;
  }finally{$zip->close();}
 }
}
