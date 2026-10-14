<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
final class ReplenishmentPermissionSeeder extends Seeder
{
    public const PERMISSIONS=['stock_policy_manage','supplier_catalog_manage','purchase_order_view','purchase_order_create','purchase_order_update','purchase_order_delete','purchase_order_approve','purchase_order_send','purchase_order_receive','purchase_order_cancel','purchase_order_surplus','purchase_order_export','purchase_suggestion_manage'];
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach(self::PERMISSIONS as $name) Permission::findOrCreate($name,'web');
        Role::where('name','Admin')->where('guard_name','web')->firstOrFail()->givePermissionTo(self::PERMISSIONS);
        Role::where('name','Demo Stock')->where('guard_name','web')->first()?->givePermissionTo(['purchase_order_view','purchase_order_create','purchase_order_update','purchase_order_receive']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
