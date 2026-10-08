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
        Schema::create('customer_debt_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->string('kind', 24);
            $t->json('payload');
            $t->string('operation_key', 96)->unique();
            $t->char('request_hash', 64);
            $t->dateTime('occurred_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Debt follow-up history must be retained.');
    }
};
