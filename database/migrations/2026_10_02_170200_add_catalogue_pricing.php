<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('catalogue_price_ttc', 20, 6)->nullable();
            $table->decimal('catalogue_reference_cost', 20, 6)->nullable();
        });
        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('sale_price_ttc', 20, 6)->nullable();
            $table->decimal('reference_purchase_cost', 20, 6)->nullable();
        });
        foreach (['price_rules', 'promotions'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
                $table->foreignId('point_of_sale_id')->nullable()->constrained()->restrictOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
                $table->string('name');
                $table->decimal('minimum_quantity', 20, 6)->default(0);
                $table->integer('priority')->default(0);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                if ($name === 'price_rules') {
                    $table->decimal('price_ttc', 20, 6);
                } else {
                    $table->foreignId('legacy_product_id')->nullable()->unique()->constrained('products')->restrictOnDelete();
                    $table->string('kind', 16);
                    $table->decimal('value', 20, 6)->default(0);
                    $table->unsignedInteger('buy_quantity')->nullable();
                    $table->unsignedInteger('free_quantity')->nullable();
                    $table->unsignedInteger('bundle_quantity')->nullable();
                    $table->decimal('bundle_price', 20, 6)->nullable();
                }
                $table->timestamps();
                $table->index(['product_unit_id', 'is_active']);
            });
        }
    }
    public function down(): void
    {
        if (DB::table('products')->whereNotNull('catalogue_price_ttc')->orWhereNotNull('catalogue_reference_cost')->exists()
            || DB::table('price_rules')->exists() || DB::table('promotions')->exists()
            || DB::table('product_units')->whereNotNull('sale_price_ttc')->orWhereNotNull('reference_purchase_cost')->exists()) {
            throw new RuntimeException('Pricing data exists; use the documented recovery procedure.');
        }
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['catalogue_price_ttc', 'catalogue_reference_cost']));
        Schema::drop('promotions');
        Schema::drop('price_rules');
        Schema::table('product_units', fn (Blueprint $table) => $table->dropColumn(['sale_price_ttc', 'reference_purchase_cost']));
    }
};
