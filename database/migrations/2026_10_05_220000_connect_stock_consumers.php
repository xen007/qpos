<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Historical documents and carts have no proven shop: retain NULL.
        foreach (['orders','purchases','pos_carts'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->foreignId('point_of_sale_id')->nullable()->constrained('points_of_sale')->restrictOnDelete());
        }
        Schema::table('pos_carts', function (Blueprint $t) {
            $t->unique(['user_id','point_of_sale_id','product_id'], 'pos_carts_user_shop_product_unique');
        });
        Schema::table('purchase_items', fn (Blueprint $t) => $t->foreignId('product_batch_id')->nullable()->constrained('product_batches')->restrictOnDelete());
    }

    public function down(): void
    {
        if (DB::table('orders')->whereNotNull('point_of_sale_id')->exists()
            || DB::table('purchases')->whereNotNull('point_of_sale_id')->exists()
            || DB::table('pos_carts')->whereNotNull('point_of_sale_id')->exists()
            || DB::table('purchase_items')->whereNotNull('product_batch_id')->exists()) {
            throw new RuntimeException('Shop/receipt evidence exists; recover with compatible code instead of discarding it.');
        }
        Schema::table('purchase_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('product_batch_id'));
        Schema::table('pos_carts', fn (Blueprint $t) => $t->dropUnique('pos_carts_user_shop_product_unique'));
        foreach (['pos_carts','purchases','orders'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->dropConstrainedForeignId('point_of_sale_id'));
        }
    }
};
