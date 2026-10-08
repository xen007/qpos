<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ReportingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $names = ['daily_summary_view', 'daily_summary_close', 'daily_summary_receive', 'reports_history'];
        foreach ($names as $name) Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        Role::where('name', 'Admin')->where('guard_name', 'web')->first()?->givePermissionTo($names);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
