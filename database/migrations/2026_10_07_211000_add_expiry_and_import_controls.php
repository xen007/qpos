<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('expiry_policy', 24)->default('non_perishable');
            $table->unsignedSmallInteger('expiry_months')->nullable();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('expiry_policy_override', 24)->nullable();
            $table->boolean('sku_auto_suffix')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['expiry_policy_override','sku_auto_suffix']));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn(['expiry_policy','expiry_months']));
    }
};
