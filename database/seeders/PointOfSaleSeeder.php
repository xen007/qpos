<?php

namespace Database\Seeders;

use App\Models\PointOfSale;
use Illuminate\Database\Seeder;

class PointOfSaleSeeder extends Seeder
{
    public function run(): void
    {
        PointOfSale::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Boutique principale', 'is_active' => true]
        );
    }
}
