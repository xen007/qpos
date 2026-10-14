<?php
namespace App\Console\Commands;
use App\Services\BackupRestoration;
use Illuminate\Console\Command;
final class VerifyBackupRestore extends Command {
 protected$signature='backups:verify {run : Verified backup ID}';
 protected$description='Restore an operational backup into a new isolated database and private directory';
 public function handle(BackupRestoration$s):int {
  try{$result=$s->verify((int)$this->argument('run'));$this->info(json_encode($result,JSON_THROW_ON_ERROR));return self::SUCCESS;}
  catch(\Throwable){$this->error('Isolated restoration failed. No source database was overwritten.');return self::FAILURE;}
 }
}
