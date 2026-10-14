<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_policies', function (Blueprint $t) {
            $t->id();
            $t->string('scope_key', 80)->unique();
            $t->foreignId('point_of_sale_id')->nullable()->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            foreach (['minimum','optimal','maximum'] as $field) $t->decimal($field,20,6)->nullable();
            $t->boolean('alerts_enabled')->nullable();
            $t->boolean('maximum_enabled')->nullable();
            $t->boolean('hide_out_of_stock')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['point_of_sale_id','product_id']);
        });
    }
    public function down(): void { throw new RuntimeException('Restore a verified backup; stock policies must not be discarded.'); }
};
