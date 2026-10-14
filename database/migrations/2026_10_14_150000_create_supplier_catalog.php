<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('supplier_catalog',function(Blueprint $t){
   $t->id();$t->foreignId('supplier_id')->constrained()->restrictOnDelete();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->foreignId('product_unit_id')->constrained()->restrictOnDelete();
   $t->string('supplier_reference',100)->nullable();$t->unsignedSmallInteger('delay_days')->default(7);$t->decimal('minimum_order',20,6)->default(1);$t->decimal('order_multiple',20,6)->default(1);$t->decimal('estimated_cost',20,6)->nullable();$t->date('price_on')->nullable();$t->boolean('preferred')->default(false);$t->boolean('is_active')->default(true);$t->timestamps();
   $t->unique(['supplier_id','product_id']);$t->index(['product_id','is_active']);
  });
 }
 public function down():void {throw new RuntimeException('Restore verified backup.');}
};
