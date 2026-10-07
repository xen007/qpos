<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->boolean('auto_generated')->default(false);
            $table->boolean('cost_unknown')->default(false);
            $table->boolean('estimated_expiry')->default(false);
            $table->index(['auto_generated', 'estimated_expiry'], 'product_batches_auto_expiry_lookup');
        });

        Schema::create('product_batch_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_batch_id')->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('operation_key', 64)->unique();
            $table->string('kind', 32);
            $table->json('before_values')->nullable();
            $table->json('after_values');
            $table->text('reason');
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['product_batch_id', 'occurred_at'], 'batch_amendments_history');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('product_batch_amendments') && Schema::hasTable('product_batches')
            && DB::table('product_batch_amendments')->exists()) {
            throw new RuntimeException('Batch amendment history exists; rollback refused.');
        }
        Schema::dropIfExists('product_batch_amendments');
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex('product_batches_auto_expiry_lookup');
            $table->dropColumn(['auto_generated', 'cost_unknown', 'estimated_expiry']);
        });
    }
};
