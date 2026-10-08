<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_line_snapshots', function (Blueprint $t) {
            $t->foreignId('order_product_id')->primary()->constrained('order_products')->restrictOnDelete();
            $t->unsignedBigInteger('category_id')->nullable();
            $t->string('category_label')->nullable();
            $t->dateTime('captured_at');
        });
        Schema::create('report_allocation_snapshots', function (Blueprint $t) {
            $t->foreignId('order_stock_allocation_id')->primary()->constrained('order_stock_allocations')->restrictOnDelete();
            $t->boolean('cost_known');
            $t->char('currency_code', 3)->nullable();
            $t->dateTime('captured_at');
        });
        Schema::create('reporting_activity', function (Blueprint $t) {
            $t->id();
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->string('source_type', 32);
            $t->unsignedBigInteger('source_id');
            $t->date('business_date');
            $t->dateTime('recorded_at');
            $t->index(['point_of_sale_id', 'business_date', 'id'], 'report_activity_day');
        });
        Schema::create('daily_summaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->date('business_date');
            $t->string('closure_type', 16);
            $t->unsignedInteger('version');
            $t->dateTime('cutoff_at');
            $t->unsignedBigInteger('activity_id')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->char('payload_hash', 64);
            $t->json('payload');
            $t->string('pdf_path', 400)->nullable();
            $t->char('pdf_sha256', 64)->nullable();
            $t->dateTime('created_at');
            $t->unique(['point_of_sale_id', 'business_date', 'closure_type'], 'daily_summary_idempotence');
            $t->unique(['point_of_sale_id', 'business_date', 'version'], 'daily_summary_version');
        });
        Schema::create('summary_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('daily_summary_id')->constrained('daily_summaries')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('kind', 24)->default('summary');
            $t->string('state', 24)->default('pending');
            $t->unsignedInteger('attempts')->default(0);
            $t->dateTime('next_attempt_at');
            $t->dateTime('claimed_at')->nullable();
            $t->dateTime('sent_at')->nullable();
            $t->string('last_error', 80)->nullable();
            $t->unique(['daily_summary_id', 'user_id', 'kind'], 'summary_delivery_dedup');
            $t->index(['state', 'next_attempt_at', 'id'], 'summary_delivery_worker');
        });
        Schema::create('summary_delivery_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('summary_delivery_id')->constrained('summary_deliveries')->restrictOnDelete();
            $t->unsignedInteger('attempt');
            $t->string('result', 24);
            $t->dateTime('recorded_at');
            $t->string('error_code', 80)->nullable();
        });
        Schema::create('reporting_pauses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('active_user_id')->nullable()->unique();
            $t->dateTime('started_at');
            $t->dateTime('ended_at')->nullable();
        });
        Schema::create('reporting_runtime', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value');
        });
        DB::table('reporting_runtime')->insert(['key' => 'activated_at', 'value' => now('UTC')->format('Y-m-d H:i:s')]);
        Schema::table('sale_corrections', fn (Blueprint $t) => $t->index(['created_at', 'order_id'], 'report_corrections_period'));
        Schema::table('expenses', fn (Blueprint $t) => $t->index(['point_of_sale_id', 'occurred_at'], 'report_expenses_period'));
        Schema::table('cash_sessions', fn (Blueprint $t) => $t->index(['point_of_sale_id', 'closed_at'], 'report_sessions_period'));
        $hasOrderRangeIndex = collect(Schema::getIndexes('orders'))->contains(fn ($index) => array_slice($index['columns'], 0, 2) === ['point_of_sale_id', 'created_at']);
        if (! $hasOrderRangeIndex) Schema::table('orders', fn (Blueprint $t) => $t->index(['point_of_sale_id', 'created_at', 'id'], 'report_orders_period'));
    }

    public function down(): void
    {
        throw new RuntimeException('Reporting evidence is retained. Use the documented backup restoration procedure.');
    }
};
