<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        Schema::table('products', function (Blueprint $table) {
            // Null signifie inconnu pour les produits historiques.
            $table->boolean('allows_fractional')->nullable();
        });
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->string('code', 64);
            $table->string('label', 255);
            $table->decimal('factor', 20, 6);
            $table->boolean('is_reference')->default(false);
            $table->boolean('is_active')->default(true);
            // UNIQUE nullable : une reference maximum, plusieurs autres formats.
            $table->unsignedBigInteger('reference_product_id')->nullable()
                ->storedAs('CASE WHEN is_reference = 1 THEN product_id ELSE NULL END');
            $table->unique('reference_product_id');
            $table->unique(['product_id', 'code']);
            $table->index(['product_id', 'is_active']);
            $table->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_units ADD CONSTRAINT product_units_factor_positive CHECK (factor > 0)');
            DB::statement('ALTER TABLE product_units ADD CONSTRAINT product_units_reference_valid CHECK (is_reference = 0 OR (factor = 1 AND is_active = 1))');
        }
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
            $table->string('barcode', 128)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('order_products', function (Blueprint $table) {
            $table->foreignId('product_unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('unit_id_snapshot')->nullable();
            $table->string('unit_label_snapshot')->nullable();
            $table->decimal('factor_used', 20, 6)->nullable();
            $table->decimal('base_quantity', 20, 6)->nullable();
        });
    }

    public function down(): void
    {
        // Une extension utilisee ne se retire pas par rollback destructif.
        if (DB::table('units')->where('is_active', false)->exists()
            || DB::table('product_units')->exists()
            || DB::table('products')->whereNotNull('allows_fractional')->exists()
            || DB::table('order_products')->whereNotNull('product_unit_id')->exists()
            || DB::table('order_products')->whereNotNull('unit_id_snapshot')->exists()
            || DB::table('order_products')->whereNotNull('unit_label_snapshot')->exists()
            || DB::table('order_products')->whereNotNull('factor_used')->exists()
            || DB::table('order_products')->whereNotNull('base_quantity')->exists()) {
            throw new RuntimeException('Catalogue unit data exists; use the documented recovery procedure.');
        }
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_unit_id');
            $table->dropColumn(['unit_id_snapshot', 'unit_label_snapshot', 'factor_used', 'base_quantity']);
        });
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('product_units');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('allows_fractional'));
        Schema::table('units', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
