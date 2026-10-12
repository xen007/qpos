<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StartUpSeeder::class,
        ]);

        $database = DB::connection()->getDatabaseName();
        if (app()->environment('local') && getenv('QPOS_DEMO_SEED_TARGET') === $database) {
            $this->call(CameroonDemoSeeder::class);
        }
    }
}
