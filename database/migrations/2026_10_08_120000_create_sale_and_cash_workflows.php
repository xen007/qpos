<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDatabaseName() !== DB::connection()->getDatabaseName()) {
            throw new RuntimeException('Schema and business connections must match.');
        }
        Schema::table('customers', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
            $t->string('internal_code', 24)->nullable()->unique();
        });
        $walking = DB::table('customers')->where('name', 'Walking Customer')->get();
        if ($walking->count() === 0
            && DB::table('customers')->count() === 0
            && DB::table('orders')->count() === 0) {
            // Fresh installations receive the walking customer from the seeder.
        } elseif ($walking->count() === 1) {
            DB::table('customers')->where('id', $walking->first()->id)->update(['internal_code' => 'walking']);
        } else {
            throw new RuntimeException('Exactly one Walking Customer must be identified before migration.');
        }
        Schema::table('orders', function (Blueprint $t) {
            $t->dropForeign(['customer_id']);
            $t->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
        });
        Schema::create('cash_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('active_user_id')->nullable()->unique();
            $t->string('state', 16)->default('open');
            $t->char('currency_code', 3)->default('XAF');
            $t->decimal('opening_amount', 20, 6);
            $t->decimal('counted_amount', 20, 6)->nullable();
            $t->decimal('expected_amount', 20, 6)->nullable();
            $t->decimal('difference', 20, 6)->nullable();
            $t->foreignId('handover_from_id')->nullable()->unique()->constrained('cash_sessions')->restrictOnDelete();
            $t->foreignId('handover_to_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->dateTime('opened_at');
            $t->dateTime('closed_at')->nullable();
            $t->string('operation_key', 96)->unique();
            $t->char('request_hash', 64);
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->foreign('cash_session_id')->references('id')->on('cash_sessions')->restrictOnDelete();
            $t->json('receipt_snapshot')->nullable();
        });
        Schema::create('cash_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cash_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('payment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->string('kind', 32);
            $t->string('direction', 8);
            $t->decimal('amount', 20, 6);
            $t->string('operation_key', 96)->unique();
            $t->char('request_hash', 64);
            $t->string('reason', 500);
            $t->dateTime('occurred_at');
            $t->timestamps();
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->foreignId('cash_session_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('operation_key', 96)->nullable()->unique();
            $t->char('request_hash', 64)->nullable();
            $t->string('sale_state', 24)->default('legacy');
            $t->char('currency_code', 3)->nullable();
            $t->date('due_date')->nullable();
            $t->decimal('unrounded_total', 20, 6)->nullable();
            $t->decimal('rounding_adjustment', 20, 6)->nullable();
            $t->decimal('corrected_total', 20, 6)->default(0);
            $t->decimal('credit_used', 20, 6)->default(0);
            $t->string('cart_id', 64)->nullable();
            $t->json('checkout_snapshot')->nullable();
        });
        foreach (['discount', 'sub_total', 'total', 'paid', 'due', 'change_amount'] as $field) {
            Schema::table('orders', fn (Blueprint $t) => $t->decimal($field, 20, 6)->default(0)->change());
        }
        Schema::table('pos_carts', fn (Blueprint $t) => $t->index('user_id', 'pos_carts_user_owner_index'));
        Schema::table('pos_carts', function (Blueprint $t) {
            $t->dropUnique('pos_carts_user_shop_product_unique');
            $t->string('cart_id', 64)->nullable();
            $t->foreignId('product_unit_id')->nullable()->constrained()->restrictOnDelete();
            $t->decimal('quantity', 20, 6)->default(1)->change();
            $t->unique(['user_id', 'point_of_sale_id', 'cart_id', 'product_unit_id'], 'pos_carts_owner_shop_cart_unit');
        });
        Schema::table('order_products', function (Blueprint $t) {
            $t->decimal('quantity', 20, 6)->default(1)->change();
            $t->string('packaging_label_snapshot')->nullable();
            $t->string('product_label_snapshot')->nullable();
            $t->json('pricing_snapshot')->nullable();
            $t->decimal('returned_quantity', 20, 6)->default(0);
            $t->decimal('return_value_used', 20, 6)->default(0);
            $t->decimal('effective_total', 20, 6)->nullable();
        });
        foreach (['price', 'purchase_price', 'discount', 'sub_total', 'total'] as $field) {
            Schema::table('order_products', fn (Blueprint $t) => $t->decimal($field, 20, 6)->default(0)->change());
        }
        Schema::create('sale_corrections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->foreignId('cash_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('kind', 24);
            $t->decimal('amount', 20, 6);
            $t->decimal('debt_reduction', 20, 6);
            $t->decimal('settled_amount', 20, 6);
            $t->string('operation_key', 96)->unique();
            $t->char('request_hash', 64);
            $t->string('reason', 500);
            $t->foreignId('exchange_order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $t->json('snapshot');
            $t->timestamps();
        });
        Schema::create('return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_correction_id')->constrained()->restrictOnDelete();
            $t->foreignId('order_product_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity', 20, 6);
            $t->decimal('base_quantity', 20, 6);
            $t->decimal('amount', 20, 6);
            $t->boolean('saleable');
            $t->timestamps();
        });
        Schema::create('return_stock_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('return_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('order_stock_allocation_id')->constrained()->restrictOnDelete();
            $t->foreignId('stock_movement_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity', 20, 6);
        });
        Schema::create('credit_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('sale_correction_id')->unique()->constrained()->restrictOnDelete();
            $t->char('currency_code', 3)->default('XAF');
            $t->decimal('amount', 20, 6);
            $t->decimal('remaining_amount', 20, 6);
            $t->date('expires_on')->nullable();
            $t->timestamps();
        });
        Schema::create('credit_note_uses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credit_note_id')->constrained()->restrictOnDelete();
            $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->decimal('amount', 20, 6);
            $t->timestamps();
            $t->unique(['credit_note_id', 'order_id']);
        });
        Schema::create('expense_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('cash_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $t->foreignId('cash_movement_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->decimal('amount', 20, 6);
            $t->char('currency_code', 3)->default('XAF');
            $t->string('method', 24);
            $t->string('description', 500);
            $t->string('external_reference', 128)->nullable();
            $t->string('operation_key', 96)->unique();
            $t->char('request_hash', 64);
            $t->dateTime('occurred_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Phase 4 retains financial evidence. Restore compatible code and database using the documented recovery procedure.');
    }
};
