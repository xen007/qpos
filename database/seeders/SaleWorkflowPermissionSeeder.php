<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SaleWorkflowPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $names = ['cash_session_manage', 'cash_movement_create', 'customer_credit_manage', 'sale_collect', 'sale_discount', 'sale_return', 'expense_view', 'expense_manage', 'price_labels'];
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($names);
        }
        foreach (Role::where('guard_name', 'web')->get() as $role) {
            if ($role->hasPermissionTo('sale_create')) {
                $role->givePermissionTo('cash_session_manage');
            }
            if ($role->hasPermissionTo('sale_update')) {
                $role->givePermissionTo('sale_collect');
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
