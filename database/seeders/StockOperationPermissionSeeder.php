<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StockOperationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = Role::where('name','Admin')->where('guard_name','web')->firstOrFail();
        foreach (['stock_view','stock_transfer_dispatch','stock_transfer_receive','stock_inventory','stock_opening_approve','stock_auto_lots_manage'] as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
