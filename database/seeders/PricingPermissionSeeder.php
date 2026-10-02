<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
class PricingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->firstOrFail();
        foreach (['pricing_view', 'pricing_create', 'pricing_update', 'pricing_delete'] as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
