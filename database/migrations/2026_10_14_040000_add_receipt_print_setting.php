<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('point_of_sale_settings',fn(Blueprint $t)=>$t->boolean('receipt_auto_print')->default(false));}
 public function down():void {Schema::table('point_of_sale_settings',fn(Blueprint $t)=>$t->dropColumn('receipt_auto_print'));}
};
