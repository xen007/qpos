<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_of_sale_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'point_of_sale_id']);
            $table->index(['point_of_sale_id', 'is_active', 'user_id'], 'pos_user_active_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_of_sale_user');
    }
};
