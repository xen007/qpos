<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Jobs\RunOperationalBackup;
use App\Services\{OperationalStatus,OperationalBackup,BackupArchive,SystemSettings};
use App\Support\RoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class OperationalStatusController extends Controller {
 private function authorized(Request$r):void {
  $u=$r->user()?->fresh();abort_unless($u&&!$u->is_suspended&&RoleAssignment::globalManager($u)&&$u->can('system_monitor'),403);
 }
 public function index(Request$r){
  $this->authorized($r);$status=app(OperationalStatus::class)->snapshot();$backup=app(OperationalBackup::class);
  $runs=$backup->ready()?DB::table('backup_runs')->latest('id')->paginate(20):null;
  return view('backend.operations.status',compact('status','backup','runs'));
 }
 public function settings(Request$r,SystemSettings$s){
  $this->authorized($r);$v=$r->validate([
   'enabled'=>'nullable|boolean','cloud_enabled'=>'nullable|boolean','retention_enabled'=>'nullable|boolean','retention_days'=>'required|integer|min:7|max:3650',
   'cloud_bucket'=>'nullable|string|min:3|max:63|regex:/^[a-z0-9][a-z0-9.-]+[a-z0-9]$/',
   'cloud_endpoint'=>'nullable|url:https|max:500','cloud_prefix'=>'required|string|max:120|regex:/^[a-zA-Z0-9_-]+(\/[a-zA-Z0-9_-]+)*$/','cloud_consent'=>'sometimes|accepted',
  ]);
  if($r->boolean('cloud_enabled')&&(!$r->boolean('cloud_consent')||empty($v['cloud_bucket'])))throw ValidationException::withMessages(['cloud_consent'=>__('operations.cloud_consent_required')]);
  $s->update(['backup_enabled'=>$r->boolean('enabled'),'backup_cloud_enabled'=>$r->boolean('cloud_enabled'),'backup_retention_enabled'=>$r->boolean('retention_enabled'),
   'backup_retention_days'=>(int)$v['retention_days'],'backup_cloud_bucket'=>$v['cloud_bucket']??null,'backup_cloud_endpoint'=>$v['cloud_endpoint']??null,'backup_cloud_prefix'=>$v['cloud_prefix']]);
  return back()->with('success',__('notifications.saved'));
 }
 public function run(Request$r){
  $this->authorized($r);abort_unless(app(OperationalBackup::class)->ready(),503);
  RunOperationalBackup::dispatch($r->user()->id,false)->onConnection('backup_database')->onQueue('qpos-backups')->afterCommit();
  return back()->with('success',__('operations.queued'));
 }
 public function download(Request$r,int$run){
  $this->authorized($r);$row=DB::table('backup_runs')->find($run);abort_unless($row&&$row->state==='verified',404);
  $archive=app(BackupArchive::class);$path=$archive->path($row->archive_path);$archive->verify($path,$row->archive_sha256);
  return response()->download($path,$row->archive_path,['Content-Type'=>'application/zip','Cache-Control'=>'private, no-store']);
 }
}
