<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $admin=Role::where('name','Admin')->where('guard_name','web')->lockForUpdate()->first();
            $before=$admin?->permissions->pluck('name')->sort()->values()->all() ?? [];
            foreach ([\Database\Seeders\PricingPermissionSeeder::class,\Database\Seeders\SaleWorkflowPermissionSeeder::class,\Database\Seeders\ReportingPermissionSeeder::class] as $seeder) {
                if ($admin) app($seeder)->run();
            }
            if ($admin) {
                app(\App\Services\AdministrationAudit::class)->record('role.permissions_registered','role',$admin->id,
                    ['permissions'=>$before],['permissions'=>$admin->fresh()->permissions->pluck('name')->sort()->values()->all()]);
            }
        });
    }
    public function down(): void
    {
        throw new RuntimeException('Native permission registration is additive; rollback requires a verified restoration.');
    }
};
