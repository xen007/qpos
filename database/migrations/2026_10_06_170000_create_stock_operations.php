<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDatabaseName()!==DB::connection()->getDatabaseName())
            throw new RuntimeException('Schema and data connections differ. Bootstrap the isolated database before loading providers.');
        Schema::create('product_import_runs', function (Blueprint $t) {
            $t->id(); $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('operation_key', 64)->unique(); $t->char('request_hash',64);
            $t->string('status',20); $t->unsignedInteger('line_count'); $t->json('report'); $t->timestamps();
        });
        Schema::create('stock_transfers', function (Blueprint $t) {
            $t->id(); $t->foreignId('source_shop_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('destination_shop_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('operation_key',64)->unique(); $t->char('request_hash',64);
            $t->string('status',20)->default('draft'); $t->text('reason');
            $t->dateTime('dispatched_at')->nullable(); $t->dateTime('closed_at')->nullable(); $t->timestamps();
        });
        Schema::create('stock_transfer_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('stock_transfer_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',20,6); $t->unique(['stock_transfer_id','product_id']); $t->timestamps();
        });
        Schema::create('stock_transfer_allocations', function (Blueprint $t) {
            $t->id(); $t->foreignId('stock_transfer_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_batch_id')->constrained()->restrictOnDelete();
            $t->foreignId('dispatch_movement_id')->constrained('stock_movements')->restrictOnDelete();
            $t->decimal('quantity',20,6); $t->decimal('received_quantity',20,6)->default(0);
            $t->decimal('returned_quantity',20,6)->default(0); $t->decimal('lost_quantity',20,6)->default(0);
            $t->timestamps();
        });
        Schema::create('stock_transfer_receipts', function (Blueprint $t) {
            $t->id(); $t->foreignId('stock_transfer_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete(); $t->string('operation_key',64)->unique();
            $t->char('request_hash',64); $t->string('kind',20); $t->text('reason'); $t->dateTime('occurred_at'); $t->timestamps();
        });
        Schema::create('stock_transfer_receipt_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('stock_transfer_receipt_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('stock_transfer_allocation_id');
            $t->foreign('stock_transfer_allocation_id','transfer_receipt_allocation_fk')->references('id')->on('stock_transfer_allocations')->restrictOnDelete();
            $t->decimal('quantity',20,6); $t->foreignId('movement_id')->constrained('stock_movements')->restrictOnDelete();
            $t->timestamps(); $t->unique(['stock_transfer_receipt_id','stock_transfer_allocation_id'],'transfer_receipt_allocation_unique');
        });
        Schema::create('inventories', function (Blueprint $t) {
            $t->id(); $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete(); $t->foreignId('validated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('operation_key',64)->unique(); $t->char('request_hash',64); $t->string('status',20)->default('counting');
            $t->text('reason'); $t->dateTime('started_at'); $t->dateTime('validated_at')->nullable(); $t->timestamps();
        });
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('inventory_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            // Unique active product/shop claim prevents overlapping scopes, including concurrent creation.
            $t->string('active_scope',64)->nullable()->unique();
            $t->unsignedBigInteger('count_watermark')->nullable(); $t->dateTime('count_started_at')->nullable();
            $t->dateTime('counted_at')->nullable(); $t->json('count_values')->nullable(); $t->json('adjustments')->nullable(); $t->timestamps();
            $t->unique(['inventory_id','product_id']);
        });
        Schema::create('stock_opening_approvals', function (Blueprint $t) {
            $t->id(); $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete(); $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_batch_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',20,6); $t->decimal('unit_cost',20,6); $t->string('currency_code',3);
            $t->string('operation_key',64)->unique(); $t->char('request_hash',64);
            $t->text('reason'); $t->text('evidence'); $t->dateTime('approved_at'); $t->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDatabaseName()!==DB::connection()->getDatabaseName())
            throw new RuntimeException('Schema and data connections differ; rollback refused.');
        foreach (['product_import_runs','stock_transfers','inventories','stock_opening_approvals'] as $name) {
            if (Schema::hasTable($name) && DB::table($name)->exists()) throw new RuntimeException('Stock operation evidence exists; restore a verified compatible backup instead.');
        }
        foreach (['stock_opening_approvals','inventory_items','inventories','stock_transfer_receipt_items','stock_transfer_receipts','stock_transfer_allocations','stock_transfer_items','stock_transfers','product_import_runs'] as $name) Schema::dropIfExists($name);
    }
};
