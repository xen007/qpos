<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications',fn(Blueprint $t)=>$t->index(['notifiable_type','notifiable_id','read_at'],'notifications_user_unread'));
        (new \Database\Seeders\OperationalAccessSeeder)->run();
    }
    public function down(): void
    {
        Schema::table('notifications',fn(Blueprint $t)=>$t->dropIndex('notifications_user_unread'));
        // Access references remain additive; never erase later direct/role grants on rollback.
    }
};
