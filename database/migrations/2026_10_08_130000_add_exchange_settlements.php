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
            throw new RuntimeException('Connection mismatch.');
        }
        Schema::table('orders', fn (Blueprint $t) => $t->decimal('exchange_value', 20, 6)->default(0));
        Schema::create('exchange_settlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_correction_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $t->decimal('amount', 20, 6);
            $t->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE cash_sessions ADD CONSTRAINT cash_sessions_state CHECK ((state='open' AND active_user_id=user_id AND closed_at IS NULL) OR (state='closed' AND active_user_id IS NULL AND closed_at IS NOT NULL))");
            DB::statement('ALTER TABLE cash_sessions ADD CONSTRAINT cash_sessions_amounts CHECK (opening_amount >= 0 AND (counted_amount IS NULL OR counted_amount >= 0))');
            DB::statement("ALTER TABLE cash_movements ADD CONSTRAINT cash_movements_amount_direction CHECK (amount > 0 AND direction IN ('in','out'))");
            DB::statement('ALTER TABLE credit_notes ADD CONSTRAINT credit_notes_balance CHECK (amount > 0 AND remaining_amount >= 0 AND remaining_amount <= amount)');
            DB::statement('ALTER TABLE credit_note_uses ADD CONSTRAINT credit_note_uses_positive CHECK (amount > 0)');
            DB::statement('ALTER TABLE return_items ADD CONSTRAINT return_items_positive CHECK (quantity > 0 AND base_quantity > 0 AND amount >= 0)');
            DB::statement('ALTER TABLE return_stock_allocations ADD CONSTRAINT return_stock_allocations_positive CHECK (quantity > 0)');
            DB::statement('ALTER TABLE exchange_settlements ADD CONSTRAINT exchange_settlements_positive CHECK (amount > 0)');
            DB::statement('ALTER TABLE expenses ADD CONSTRAINT expenses_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Financial evidence must be preserved.');
    }
};
