<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('saleable_quantity', 20, 6)->default(0);
            $table->decimal('unsaleable_quantity', 20, 6)->default(0);
            $table->decimal('in_transit_quantity', 20, 6)->default(0);
            $table->decimal('unallocated_opening_quantity', 20, 6)->default(0);
            $table->timestamps();
            $table->unique(['point_of_sale_id', 'product_id'], 'product_stock_shop_product_unique');
        });

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 128)->nullable();
            $table->string('expiry_status', 20);
            $table->date('expires_on')->nullable();
            $table->dateTime('received_at');
            $table->decimal('unit_cost', 20, 6)->nullable();
            // Purchase receipt FK is added by the purchase lot when that table exists.
            $table->unsignedBigInteger('purchase_receipt_item_id')->nullable();
            $table->string('provenance', 40);
            $table->timestamps();
            $table->index(['product_id', 'expiry_status', 'expires_on', 'received_at', 'id'], 'product_batches_fefo_lookup');
            $table->index(['product_id', 'expiry_status', 'received_at', 'id'], 'product_batches_fifo_lookup');
        });

        Schema::create('batch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $table->foreignId('product_batch_id')->constrained('product_batches')->restrictOnDelete();
            $table->decimal('saleable_quantity', 20, 6)->default(0);
            $table->decimal('unsaleable_quantity', 20, 6)->default(0);
            $table->timestamps();
            $table->unique(['point_of_sale_id', 'product_batch_id'], 'batch_stock_shop_batch_unique');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->string('bucket', 32);
            $table->decimal('quantity_delta', 20, 6);
            $table->string('type', 40);
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('correlation_key', 64);
            $table->unsignedInteger('correlation_line')->default(1);
            $table->foreignId('order_product_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('conversion_run_id')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('stock_movements')->restrictOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->unique(['point_of_sale_id', 'correlation_key', 'correlation_line'], 'stock_movements_correlation_unique');
            $table->index(['point_of_sale_id', 'product_id', 'bucket', 'occurred_at', 'id'], 'stock_movements_ledger_lookup');
            $table->index(['order_product_id', 'id'], 'stock_movements_order_product_lookup');
        });

        Schema::create('order_stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_movement_id')->constrained('stock_movements')->restrictOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->decimal('base_quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('total_cost', 20, 6)->nullable();
            $table->string('provenance', 40);
            $table->timestamps();
            $table->unique('stock_movement_id', 'order_stock_allocations_movement_unique');
            $table->index(['order_product_id', 'id'], 'order_stock_allocations_line_lookup');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_stock ADD CONSTRAINT product_stock_quantities_nonnegative CHECK (saleable_quantity >= 0 AND unsaleable_quantity >= 0 AND in_transit_quantity >= 0 AND unallocated_opening_quantity >= 0)');
            DB::statement('ALTER TABLE batch_stock ADD CONSTRAINT batch_stock_quantities_nonnegative CHECK (saleable_quantity >= 0 AND unsaleable_quantity >= 0)');
            DB::statement("ALTER TABLE product_batches ADD CONSTRAINT product_batches_expiry_status_valid CHECK ((expiry_status = 'dated' AND expires_on IS NOT NULL) OR (expiry_status IN ('not_applicable', 'unknown') AND expires_on IS NULL))");
            DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movement_delta_nonzero CHECK (quantity_delta <> 0)');
            DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movement_bucket_valid CHECK (bucket IN ('saleable', 'unsaleable', 'in_transit', 'unallocated_opening'))");
            DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movement_type_valid CHECK (type IN ('receipt', 'opening', 'opening_approved', 'sale', 'return', 'transfer_out', 'transfer_in', 'loss', 'inventory_adjustment', 'adjustment'))");
            DB::statement('ALTER TABLE order_stock_allocations ADD CONSTRAINT order_stock_allocation_quantity_positive CHECK (base_quantity > 0)');
        }
    }

    public function down(): void
    {
        if ((Schema::hasTable('stock_movements') && DB::table('stock_movements')->exists())
            || (Schema::hasTable('product_stock') && DB::table('product_stock')->exists())
            || (Schema::hasTable('product_batches') && DB::table('product_batches')->exists())
            || (Schema::hasTable('batch_stock') && DB::table('batch_stock')->exists())) {
            throw new RuntimeException('Stock records exist; use the documented recovery procedure instead of rolling back stock history.');
        }

        Schema::dropIfExists('order_stock_allocations');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('batch_stock');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('product_stock');
    }
};
