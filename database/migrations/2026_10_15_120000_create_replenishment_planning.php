<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('replenishment_settings', function (Blueprint $t) {
            $t->id(); $t->foreignId('point_of_sale_id')->unique()->constrained('points_of_sale')->restrictOnDelete();
            $t->string('schedule', 16)->default('on_demand'); $t->unsignedSmallInteger('window_days')->default(30);
            $t->unsignedSmallInteger('review_days')->default(7); $t->unsignedSmallInteger('safety_days')->default(3);
            $t->unsignedSmallInteger('minimum_history_days')->default(14);
            $t->string('daily_time', 5)->default('21:00'); $t->unsignedTinyInteger('weekly_day')->default(1);
            $t->string('weekly_time', 5)->default('08:00'); $t->boolean('critical_immediate')->default(true);
            $t->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->json('recipient_ids'); $t->unsignedInteger('version')->default(1);
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
        });
        Schema::create('replenishment_runs', function (Blueprint $t) {
            $t->id(); $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('slot', 96); $t->string('request_hash', 64); $t->string('trigger', 16);
            $t->string('state', 16)->default('queued'); $t->json('settings_snapshot');
            $t->unsignedBigInteger('cursor')->default(0); $t->unsignedSmallInteger('attempts')->default(0);
            $t->string('last_error', 100)->nullable(); $t->timestamp('available_at'); $t->timestamp('completed_at')->nullable();
            $t->timestamps(); $t->unique(['point_of_sale_id', 'slot']); $t->index(['state', 'available_at', 'id']);
        });
        Schema::create('replenishment_run_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('replenishment_run_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('state', 16); $t->json('snapshot'); $t->timestamps();
            $t->unique(['replenishment_run_id', 'product_id'], 'replenishment_run_product');
        });
    }
    public function down(): void
    {
        throw new RuntimeException('Planning history is retained. Restore a verified backup.');
    }
};
