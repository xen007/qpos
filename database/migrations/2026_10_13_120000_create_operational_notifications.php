<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications',function(Blueprint $t){$t->uuid('id')->primary();$t->string('type');$t->morphs('notifiable');$t->text('data');$t->timestamp('read_at')->nullable();$t->timestamps();});
        Schema::create('notification_events',function(Blueprint $t){
            $t->id();$t->char('event_key',64)->unique();$t->string('kind',40);$t->unsignedBigInteger('point_of_sale_id')->nullable();$t->unsignedBigInteger('target_user_id')->nullable();$t->unsignedBigInteger('source_id')->nullable();$t->json('payload');
            $t->string('state',20)->default('pending');$t->unsignedSmallInteger('attempts')->default(0);$t->timestamp('queued_at')->nullable();$t->timestamp('available_at')->nullable();$t->timestamp('processed_at')->nullable();$t->string('last_error',80)->nullable();$t->timestamps();$t->index(['state','available_at','id']);
        });
        Schema::create('notification_deliveries',function(Blueprint $t){
            $t->id();$t->foreignId('notification_event_id')->constrained('notification_events');$t->foreignId('user_id')->constrained('users');$t->string('channel',20);$t->char('delivery_key',64)->unique();$t->uuid('notification_id')->nullable();$t->string('state',20);$t->unsignedSmallInteger('attempts')->default(0);$t->timestamp('delivered_at')->nullable();$t->timestamps();$t->index(['user_id','state','id']);
        });
        Schema::create('notification_preferences',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained('users');$t->string('kind',40);$t->string('channel',20);$t->boolean('enabled')->default(true);$t->timestamps();$t->unique(['user_id','kind','channel']);});
        Schema::create('notification_states',function(Blueprint $t){$t->string('key',150)->primary();$t->string('value',100);$t->unsignedInteger('generation')->default(0);$t->timestamps();});
        Schema::create('notification_jobs',function(Blueprint $t){$t->bigIncrements('id');$t->string('queue')->index();$t->longText('payload');$t->unsignedTinyInteger('attempts');$t->unsignedInteger('reserved_at')->nullable();$t->unsignedInteger('available_at');$t->unsignedInteger('created_at');});
    }
    public function down(): void
    {
        foreach(['notification_jobs','notification_states','notification_preferences','notification_deliveries','notification_events','notifications'] as $table)Schema::dropIfExists($table);
    }
};
