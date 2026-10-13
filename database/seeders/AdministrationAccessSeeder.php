<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\PermissionRegistrar;

class AdministrationAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission=Permission::firstOrCreate(['name'=>'user_permissions_manage','guard_name'=>'web']);
        Role::where('name','Admin')->where('guard_name','web')->first()?->givePermissionTo($permission);
        $role=Role::firstOrCreate(['name'=>'shop_admin','guard_name'=>'web']);
        $names=\App\Support\UserAccessSettings::shopAdminCapabilities();
        // Existing references only; no speculative permission identifiers.
        if($role->wasRecentlyCreated){
            $role->givePermissionTo(Permission::where('guard_name','web')->whereIn('name',$names)->get());
        }else{
            foreach($role->permissions as $p)if(!in_array($p->name,$names,true))$role->revokePermissionTo($p);
        }
        $stock=Role::where('name','Demo Stock')->where('guard_name','web')->first();
        if($stock){
            // Old seed referenced stock_adjust, which has no native route consumer.
            if($stock->permissions()->where('name','stock_adjust')->exists())$stock->revokePermissionTo('stock_adjust');
            $stock->givePermissionTo(Permission::where('guard_name','web')->whereIn('name',['purchase_view','purchase_receive'])->get());
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
