<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('user_activity',function(Blueprint $t){
    $t->foreignId('user_id')->constrained();$t->unsignedBigInteger('scope_shop_id')->default(0);
    $t->dateTime('last_seen_at');$t->primary(['user_id','scope_shop_id']);$t->index(['scope_shop_id','last_seen_at']);
 });}
 public function down():void {Schema::dropIfExists('user_activity');}
};
