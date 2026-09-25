<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo data cannot be seeded in production.');
        }

        if (!User::query()->exists()) {
            throw new RuntimeException('Create an administrator before seeding demo data.');
        }

        $this->call([
            ProductSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            PurchaseSeeder::class,
        ]);
    }
}
