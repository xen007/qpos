<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PointOfSalePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->firstOrFail();
        foreach (['point_of_sale_view', 'point_of_sale_access', 'point_of_sale_create', 'point_of_sale_update',
            'point_of_sale_delete', 'point_of_sale_assign', 'point_of_sale_manage_all'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $admin->givePermissionTo($permission);
        }
        foreach (Role::whereIn('name', ['cashier', 'sales_associate'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo(['point_of_sale_view', 'point_of_sale_access']);
        }
        // Aucun utilisateur n'est affecte automatiquement a une boutique.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
