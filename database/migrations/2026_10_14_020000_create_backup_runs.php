<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('backup_runs',function(Blueprint$t){
  $t->id();$t->string('database_name',128);$t->string('state',20)->default('running');$t->unsignedInteger('attempts')->default(1);
  $t->string('archive_path')->nullable();$t->char('archive_sha256',64)->nullable();$t->unsignedBigInteger('archive_bytes')->nullable();
  $t->boolean('encrypted')->default(false);$t->string('cloud_state',20)->default('disabled');$t->string('cloud_key')->nullable();
  $t->string('restore_database',128)->nullable();$t->decimal('restore_seconds',12,3)->nullable();$t->timestamp('restore_verified_at')->nullable();
  $t->string('last_error',80)->nullable();$t->dateTime('started_at');$t->timestamp('completed_at')->nullable();$t->timestamps();$t->index(['state','completed_at']);
 });}
 public function down():void {Schema::dropIfExists('backup_runs');}
};
