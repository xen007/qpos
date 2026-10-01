<?php

namespace Tests\Feature;

use App\Models\PointOfSale;
use App\Models\User;
use Database\Seeders\PointOfSaleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PointOfSaleAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('username')->unique();
            $table->string('profile_image')->nullable();
            $table->string('google_id')->nullable();
            $table->boolean('is_google_registered')->default(false);
            $table->boolean('is_suspended')->default(false);
            $table->timestamps();
        });
        (require base_path('database/migrations/2026_10_01_100000_create_points_of_sale_table.php'))->up();
        (require base_path('database/migrations/2026_10_01_100100_create_point_of_sale_user_table.php'))->up();
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->unique(['name', 'guard_name']);
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->unique(['name', 'guard_name']);
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
            $table->index(['model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        $this->seed(RolePermissionSeeder::class);

    }

    public function test_user_only_lists_and_opens_an_assigned_active_store(): void
    {
        $user = $this->makeUser('cashier');
        $assigned = PointOfSale::create(['code' => 'SHOP-A', 'name' => 'Shop A']);
        $other = PointOfSale::create(['code' => 'SHOP-B', 'name' => 'Shop B']);
        $secondAssigned = PointOfSale::create(['code' => 'SHOP-C', 'name' => 'Shop C']);
        $user->pointOfSales()->attach($assigned->id, ['is_active' => true]);
        $user->pointOfSales()->attach($secondAssigned->id, ['is_active' => true]);

        $this->assertSame(
            [$assigned->id, $secondAssigned->id],
            PointOfSale::accessibleBy($user)->pluck('id')->all()
        );

        $this->assertTrue(Gate::forUser($user)->allows('view', $assigned));
        $this->assertTrue(Gate::forUser($user)->allows('view', $secondAssigned));
        $this->assertFalse(Gate::forUser($user)->allows('view', $other));

        $user->pointOfSales()->updateExistingPivot($assigned->id, ['is_active' => false]);
        $this->assertFalse(Gate::forUser($user)->allows('view', $assigned));
    }

    public function test_admin_role_does_not_bypass_store_assignment(): void
    {
        $user = $this->makeUser('Admin');
        $store = PointOfSale::create(['code' => 'ADMIN-SHOP', 'name' => 'Admin Shop']);

        $this->assertFalse(Gate::forUser($user)->allows('view', $store));

        $user->pointOfSales()->attach($store->id, ['is_active' => true]);
        $user->is_suspended = true;
        $user->save();

        $this->assertFalse(Gate::forUser($user)->allows('view', $store));
    }

    public function test_default_store_seeder_is_idempotent_and_does_not_assign_users(): void
    {
        $user = $this->makeUser('sales_associate');

        $this->seed(PointOfSaleSeeder::class);
        $this->seed(PointOfSaleSeeder::class);

        $this->assertSame(1, PointOfSale::where('code', 'MAIN')->count());
        $this->assertSame(0, $user->pointOfSales()->count());
    }

    private function makeUser(string $role): User
    {
        $user = User::create([
            'name' => 'Test '.$role,
            'email' => strtolower($role).'@example.test',
            'username' => strtolower($role),
            'password' => 'test-password',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
