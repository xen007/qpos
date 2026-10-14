<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('purchase_order_counters',function(Blueprint $t){$t->unsignedSmallInteger('year')->primary();$t->unsignedBigInteger('value')->default(0);});
  Schema::create('purchase_orders',function(Blueprint $t){
   $t->id();$t->string('reference',40)->unique();$t->foreignId('supplier_id')->constrained()->restrictOnDelete();$t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();
   $t->string('status',20)->default('draft');$t->string('mode',20)->default('manual');$t->string('currency_code',3)->default('XAF');$t->date('expected_date')->nullable();$t->text('notes')->nullable();
   $t->foreignId('purchase_id')->nullable()->unique()->constrained()->restrictOnDelete();$t->unsignedInteger('version')->default(1);$t->decimal('total_amount',20,6)->nullable();$t->json('snapshot')->nullable();$t->string('generation_key',120)->nullable()->unique();
   $t->timestamp('approved_at')->nullable();$t->timestamp('issued_at')->nullable();$t->timestamp('closed_at')->nullable();$t->timestamps();
   $t->index(['point_of_sale_id','status','created_at']);$t->index(['supplier_id','expected_date']);
  });
  Schema::create('purchase_order_items',function(Blueprint $t){
   $t->id();$t->foreignId('purchase_order_id')->constrained()->restrictOnDelete();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->foreignId('product_unit_id')->constrained()->restrictOnDelete();
   $t->foreignId('purchase_item_id')->nullable()->unique()->constrained()->restrictOnDelete();$t->decimal('quantity_ordered',20,6);$t->decimal('closed_quantity',20,6)->default(0);$t->decimal('unit_price',20,6)->nullable();$t->decimal('discount_amount',20,6)->default(0);$t->json('snapshot')->nullable();$t->json('suggestion')->nullable();$t->timestamps();$t->unique(['purchase_order_id','product_unit_id'],'po_line_packaging');
  });
  Schema::create('purchase_order_events',function(Blueprint $t){
   $t->id();$t->foreignId('purchase_order_id')->constrained()->restrictOnDelete();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->string('action',40);$t->string('operation_key',96)->unique();$t->string('request_hash',64);$t->text('reason')->nullable();$t->json('before_values')->nullable();$t->json('after_values')->nullable();$t->timestamp('occurred_at');$t->index(['purchase_order_id','id']);
  });
  Schema::table('purchase_items',function(Blueprint $t){$t->foreignId('purchase_order_item_id')->nullable()->unique()->constrained()->restrictOnDelete();});
 }
 public function down():void {throw new RuntimeException('Purchase order history is immutable; restore a verified backup.');}
};
