<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('notification_outbound',function(Blueprint$t){
            $t->id();$t->foreignId('notification_event_id')->constrained('notification_events')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('channel',16);
            $t->text('destination');$t->char('destination_hash',64);$t->char('delivery_key',64)->unique();
            $t->string('locale',2)->default('fr');$t->string('state',20)->default('pending');$t->unsignedInteger('attempts')->default(0);
            foreach(['available_at','queued_at','claimed_at','accepted_at','delivered_at']as$c)$t->timestamp($c)->nullable();
            $t->string('provider_id',40)->nullable();$t->string('last_error',80)->nullable();$t->timestamps();
            $t->index(['state','available_at','id']);
        });
    }
    public function down(): void {Schema::dropIfExists('notification_outbound');}
};
