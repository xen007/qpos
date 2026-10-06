<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_active')->default(true);
        });
        DB::table('suppliers')->where('name', 'Own Supplier')->update(['is_internal' => true]);
        Schema::table('product_batches', fn(Blueprint $table)=>$table->char('currency_code',3)->nullable());
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('product_unit_id')->nullable()->after('product_id')->constrained('product_units')->restrictOnDelete();
            $table->unsignedBigInteger('unit_id_snapshot')->nullable();
            $table->string('unit_label_snapshot')->nullable();
            $table->decimal('factor_used', 20, 6)->nullable();
            $table->decimal('entered_quantity', 20, 6)->nullable();
            $table->decimal('base_quantity', 20, 6)->nullable();
            $table->decimal('source_unit_cost', 20, 6)->nullable();
            $table->decimal('source_line_amount', 20, 6)->nullable();
            $table->string('source_line_amount_exact', 64)->nullable();
            $table->boolean('allows_fractional_snapshot')->nullable();
        });
        DB::statement('ALTER TABLE purchase_items MODIFY quantity DECIMAL(20,6) NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE purchase_items MODIFY purchase_price DECIMAL(20,6) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE purchase_items MODIFY price DECIMAL(20,6) NOT NULL DEFAULT 0');
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('due_date')->nullable();
            $table->string('payment_status', 20)->default('unknown');
            $table->string('receipt_status', 20)->default('unknown');
            $table->char('currency_code', 3)->nullable();
            $table->string('operation_key',96)->nullable()->unique();
            $table->char('request_hash',64)->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 255)->nullable();
            $table->index(['point_of_sale_id','supplier_id','date','id'],'purchases_shop_supplier_date');
            $table->index(['point_of_sale_id','payment_status','due_date','id'],'purchases_shop_due');
        });
        foreach (['sub_total','tax','discount_value','shipping','grand_total'] as $amountColumn) {
            DB::statement('ALTER TABLE purchases MODIFY '.$amountColumn.' DECIMAL(20,6) NOT NULL DEFAULT 0');
        }

        Schema::create('purchase_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key',96)->unique();
            $table->char('request_hash',64);
            $table->json('before_values');
            $table->json('after_values');
            $table->string('reason',255);
            $table->dateTime('occurred_at');
            $table->timestamps();
        });
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 96)->unique();
            $table->char('request_hash', 64);
            $table->string('status', 20)->default('received');
            $table->dateTime('received_at');
            $table->string('provenance', 32)->default('purchase');
            $table->timestamps();
            $table->index(['purchase_id', 'received_at', 'id']);
        });
        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->unsignedBigInteger('product_unit_id')->nullable();
            $table->unsignedBigInteger('unit_id_snapshot')->nullable();
            $table->string('unit_label_snapshot')->nullable();
            $table->decimal('factor_used', 20, 6);
            $table->decimal('entered_quantity', 20, 6);
            $table->decimal('base_quantity', 20, 6);
            $table->decimal('source_unit_cost', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('source_line_amount', 20, 6);
            $table->string('source_line_amount_exact', 64);
            $table->boolean('allows_fractional_snapshot');
            $table->string('expiry_status', 20);
            $table->date('expires_on')->nullable();
            $table->timestamps();
            $table->index(['purchase_item_id', 'id']);
            $table->foreign('product_unit_id')->references('id')->on('product_units')->restrictOnDelete();
        });
        Schema::table('stock_movements', fn (Blueprint $table) => $table->foreignId('purchase_receipt_item_id')->nullable()->constrained()->restrictOnDelete());
        DB::statement('ALTER TABLE product_batches ADD CONSTRAINT product_batches_receipt_item_fk FOREIGN KEY (purchase_receipt_item_id) REFERENCES purchase_receipt_items(id) ON DELETE RESTRICT');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->nullable()->constrained('points_of_sale')->restrictOnDelete();
            // The Phase 4 cash_sessions table is not present yet; its FK is deferred.
            $table->unsignedBigInteger('cash_session_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('direction', 12);
            $table->string('method', 32);
            $table->decimal('source_amount', 20, 6);
            $table->decimal('received_amount', 20, 6);
            $table->decimal('change_amount', 20, 6)->default(0);
            $table->decimal('net_amount', 20, 6);
            $table->char('currency_code', 3)->default('XAF');
            $table->string('external_reference', 128)->nullable();
            $table->string('idempotency_key', 96)->unique();
            $table->char('request_hash', 64);
            $table->string('status', 20)->default('posted');
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('payments')->restrictOnDelete();
            $table->string('reason', 255)->nullable();
            $table->string('provenance', 32)->default('live');
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['point_of_sale_id', 'occurred_at', 'id']);
            $table->index(['supplier_id', 'occurred_at', 'id']);
            $table->index(['customer_id', 'occurred_at', 'id']);
        });
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 20, 6);
            $table->string('provenance', 32)->default('live');
            $table->timestamps();
            $table->index(['purchase_id', 'id']);
            $table->index(['order_id', 'id']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payment_allocations ADD CONSTRAINT payment_allocations_one_target CHECK ((purchase_id IS NULL) <> (order_id IS NULL))");
            DB::statement("ALTER TABLE payment_allocations ADD CONSTRAINT payment_allocations_positive CHECK (amount > 0)");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_valid_party CHECK ((supplier_id IS NULL) <> (customer_id IS NULL))");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_valid_amounts CHECK (source_amount > 0 AND received_amount > 0 AND change_amount >= 0 AND net_amount > 0 AND net_amount = received_amount - change_amount)");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_valid_direction CHECK (direction IN ('outgoing','incoming'))");
            DB::statement("ALTER TABLE purchase_receipt_items ADD CONSTRAINT purchase_receipt_items_positive CHECK (factor_used > 0 AND entered_quantity > 0 AND base_quantity > 0 AND source_unit_cost >= 0 AND unit_cost >= 0 AND source_line_amount >= 0)");
            DB::statement("ALTER TABLE purchase_receipt_items ADD CONSTRAINT purchase_receipt_items_expiry CHECK ((expiry_status = 'dated' AND expires_on IS NOT NULL) OR (expiry_status IN ('unknown','not_applicable') AND expires_on IS NULL))");
        }
    }

    public function down(): void
    {
        if (DB::table('purchase_receipt_items')->exists() || DB::table('purchase_amendments')->exists() || DB::table('payments')->exists() || DB::table('payment_allocations')->exists()
            || DB::table('purchases')->whereNotNull('currency_code')->exists() || DB::table('product_batches')->whereNotNull('currency_code')->exists() || DB::table('suppliers')->where('is_active', false)->exists()) {
            throw new RuntimeException('Purchase receipts or payments exist; use the documented recovery procedure.');
        }
        DB::statement('ALTER TABLE product_batches DROP FOREIGN KEY product_batches_receipt_item_fk');
        Schema::table('product_batches',fn(Blueprint $table)=>$table->dropColumn('currency_code'));
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('purchase_receipt_item_id'));
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('purchase_receipt_items');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('purchase_amendments');
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropIndex('purchases_shop_supplier_date');
            $table->dropIndex('purchases_shop_due');
            $table->dropUnique(['operation_key']);
            $table->dropColumn(['due_date', 'payment_status', 'receipt_status', 'currency_code', 'operation_key', 'request_hash', 'cancelled_at', 'cancellation_reason']);
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_unit_id');
            $table->dropColumn(['unit_id_snapshot', 'unit_label_snapshot', 'factor_used', 'entered_quantity', 'base_quantity', 'source_unit_cost', 'source_line_amount', 'source_line_amount_exact', 'allows_fractional_snapshot']);
        });
        Schema::table('suppliers', fn (Blueprint $table) => $table->dropColumn(['is_internal', 'is_active']));
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });
        DB::statement('ALTER TABLE purchase_items MODIFY quantity INT NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE purchase_items MODIFY purchase_price DOUBLE(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE purchase_items MODIFY price DOUBLE(10,2) NOT NULL DEFAULT 0');
        foreach (['sub_total','tax','discount_value','shipping','grand_total'] as $amountColumn) {
            DB::statement('ALTER TABLE purchases MODIFY '.$amountColumn.' DOUBLE(10,2) NOT NULL DEFAULT 0');
        }
    }
};
