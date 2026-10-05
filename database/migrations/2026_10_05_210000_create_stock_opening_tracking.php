<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_conversion_runs', function (Blueprint $t) {
            $t->id();
            $t->string('backup_sha256', 64);
            $t->string('mapping_sha256', 64);
            $t->string('source_sha256', 64);
            $t->dateTime('cutoff_at');
            $t->string('status', 32);
            $t->json('counts')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_opening_sources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversion_run_id')->constrained('stock_conversion_runs')->restrictOnDelete();
            $t->foreignId('product_id')->unique()->constrained()->restrictOnDelete();
            $t->decimal('source_quantity', 20, 6);
            $t->date('source_expire_date')->nullable();
            $t->json('source_values');
            $t->json('allocation');
            $t->timestamps();
        });
        Schema::table('stock_movements', function (Blueprint $t) {
            $t->foreign('conversion_run_id')->references('id')->on('stock_conversion_runs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('stock_conversion_runs')->exists()) {
            throw new RuntimeException('Opening evidence must be preserved; use the recovery procedure.');
        }
        Schema::table('stock_movements', fn (Blueprint $t) => $t->dropForeign(['conversion_run_id']));
        Schema::dropIfExists('stock_opening_sources');
        Schema::dropIfExists('stock_conversion_runs');
    }
};
