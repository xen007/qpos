<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->foreignId('preferred_point_of_sale_id')
            ->nullable()->constrained('points_of_sale')->restrictOnDelete());
    }
    public function down(): void
    {
        if (DB::table('users')->whereNotNull('preferred_point_of_sale_id')->exists()) {
            throw new RuntimeException('Store preferences exist; use the documented recovery procedure.');
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('preferred_point_of_sale_id'));
    }
};
