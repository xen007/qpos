<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                // Scalar references preserve evidence when an account or object is deleted.
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->unsignedBigInteger('point_of_sale_id')->nullable();
                $table->string('event', 100);
                $table->string('object_type', 100);
                $table->string('object_id', 64)->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('result', 20)->default('success');
                $table->string('reason', 512)->nullable();
                $table->uuid('correlation_id');
                $table->dateTime('occurred_at');
                $table->dateTime('recorded_at');
                $table->index(['point_of_sale_id','recorded_at','id'], 'audit_shop_date');
                $table->index(['object_type','object_id','recorded_at'], 'audit_object_date');
                $table->index(['event','recorded_at'], 'audit_event_date');
            });
        }
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->string('key', 80)->primary();
                $table->json('value');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->dateTime('updated_at');
            });
        }
        $permission = Permission::firstOrCreate(['name'=>'audit_view','guard_name'=>'web']);
        Role::where('name','Admin')->where('guard_name','web')->first()?->givePermissionTo($permission);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new RuntimeException('Administration audit/settings rollback requires a verified restoration; evidence must not be deleted.');
    }
};
