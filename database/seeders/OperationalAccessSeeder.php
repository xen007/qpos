<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission,Role};
use Spatie\Permission\PermissionRegistrar;

final class OperationalAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission=Permission::firstOrCreate(['name'=>'system_monitor','guard_name'=>'web']);
        Role::where('name','Admin')->where('guard_name','web')->first()?->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
