<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users',function(Blueprint $table){
            $table->foreignId('fixed_point_of_sale_id')->nullable()->constrained('points_of_sale')->restrictOnDelete();
        });
        (new \Database\Seeders\AdministrationAccessSeeder)->run();
    }
    public function down(): void
    {
        throw new \LogicException('Restore a verified backup to revert administrative evidence; no destructive automatic rollback.');
    }
};
