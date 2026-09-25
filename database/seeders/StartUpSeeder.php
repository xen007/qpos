<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class StartUpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Customer::firstOrCreate(['name' => 'Walking Customer'], [
            'phone' => '012345678',
        ]);
        Supplier::firstOrCreate(['name' => 'Own Supplier'], [
            'phone' => '012345678',
        ]);

        $this->call([
            UnitSeeder::class,
            CurrencySeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
