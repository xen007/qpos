<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\Models\{Permission, Role};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id(); $t->string('code',24)->unique(); $t->string('label',80);
            $t->boolean('physical_cash')->default(false); $t->boolean('reference_required')->default(true);
            $t->boolean('sale_enabled')->default(true); $t->boolean('default_active')->default(false);
        });
        foreach (['cash'=>'Cash','card'=>'External card','bank_transfer'=>'Bank transfer','orange_money'=>'Orange Money','mtn_momo'=>'MTN Mobile Money','wave'=>'Wave','cheque'=>'Cheque','transfer'=>'Internal transfer'] as $code=>$label) {
            DB::table('payment_methods')->insert(['code'=>$code,'label'=>$label,'physical_cash'=>$code==='cash','reference_required'=>$code!=='cash','sale_enabled'=>$code!=='transfer','default_active'=>in_array($code,['cash','card','bank_transfer'],true)]);
        }
        Schema::create('point_of_sale_payment_method', function (Blueprint $t) {
            $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $t->boolean('is_active')->default(false); $t->unique(['point_of_sale_id','payment_method_id'],'shop_payment_method_unique');
        });
        Schema::create('point_of_sale_settings', function (Blueprint $t) {
            $t->foreignId('point_of_sale_id')->primary()->constrained('points_of_sale')->restrictOnDelete();
            $t->boolean('pending_sale_enabled')->default(false); $t->unsignedInteger('pending_expiry_minutes')->default(240);
            $t->unsignedInteger('taken_lease_minutes')->default(10); $t->unsignedInteger('orphan_idle_minutes')->default(720);
            $t->timestamps();
        });
        DB::statement('ALTER TABLE payments MODIFY method VARCHAR(24) NOT NULL');
        Schema::table('payments', fn (Blueprint $t) => $t->foreignId('original_payment_id')->nullable()->constrained('payments')->restrictOnDelete());
        Schema::table('orders', fn (Blueprint $t) => $t->foreignId('prepared_by_user_id')->nullable()->constrained('users')->restrictOnDelete());
        Schema::create('pending_sales', function (Blueprint $t) {
            $t->id(); $t->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $t->foreignId('seller_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('cashier_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete(); $t->char('currency_code',3)->default('XAF');
            $t->string('submission_key',64); $t->char('request_hash',64); $t->char('proposed_quote_hash',64);
            $t->decimal('proposed_total',20,6); $t->decimal('order_discount',20,6)->default(0);
            $t->string('state',16)->default('pending'); $t->dateTime('expires_at'); $t->dateTime('taken_at')->nullable();
            $t->dateTime('lease_expires_at')->nullable(); $t->foreignId('completed_order_id')->nullable()->unique()->constrained('orders')->restrictOnDelete();
            $t->string('final_operation_key',64)->nullable()->unique(); $t->timestamps();
            $t->unique(['point_of_sale_id','submission_key'],'pending_submission_unique');
            $t->index(['point_of_sale_id','state','expires_at'],'pending_queue_index');
        });
        Schema::create('pending_sale_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('pending_sale_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_unit_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',20,6); $t->json('pricing_snapshot'); $t->unique(['pending_sale_id','product_unit_id']);
        });
        Schema::create('pending_sale_events', function (Blueprint $t) {
            $t->id(); $t->foreignId('pending_sale_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('kind',24); $t->string('reason',500)->nullable(); $t->json('payload')->nullable(); $t->dateTime('occurred_at');
        });
        Schema::create('cash_session_supervisions', function (Blueprint $t) {
            $t->id(); $t->foreignId('cash_session_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->decimal('counted_amount',20,6); $t->decimal('expected_amount',20,6); $t->decimal('difference',20,6);
            $t->string('reason',500); $t->string('operation_key',64)->unique(); $t->char('request_hash',64); $t->dateTime('occurred_at');
        });
        foreach (['payment_methods_manage','pending_sale_prepare','pending_sale_collect','cash_session_supervise'] as $name) {
            $p=Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']); Role::where('name','Admin')->first()?->givePermissionTo($p);
        }
        foreach (['cashier','Demo Cashier'] as $name) Role::where('name',$name)->first()?->givePermissionTo('pending_sale_collect');
        foreach (['sales_associate','Demo Seller'] as $name) Role::where('name',$name)->first()?->givePermissionTo('pending_sale_prepare');
        app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void { throw new RuntimeException('Restore a verified database and private files; destructive workflow rollback is refused.'); }
};
