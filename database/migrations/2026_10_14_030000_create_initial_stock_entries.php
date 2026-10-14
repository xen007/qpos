<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {Schema::create('initial_stock_entries',function(Blueprint $t){
        $t->id();$t->foreignId('point_of_sale_id')->constrained('points_of_sale');
        $t->foreignId('product_id')->constrained();$t->foreignId('user_id')->constrained();
        $t->unsignedBigInteger('approval_id')->unique();$t->string('opening_key',64)->unique();
        $t->string('operation_key',64)->unique();$t->char('request_hash',64);$t->dateTime('recorded_at');
        $t->index(['point_of_sale_id','product_id']);
    });}
    public function down():void {Schema::dropIfExists('initial_stock_entries');}
};
