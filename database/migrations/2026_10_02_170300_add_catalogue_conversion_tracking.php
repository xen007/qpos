<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogue_conversion_runs', function (Blueprint $t) {
            $t->uuid('id')->primary(); $t->string('backup_sha256', 64); $t->string('status', 32);
            $t->json('counts')->nullable(); $t->timestamp('completed_at')->nullable(); $t->timestamps();
        });
        Schema::create('catalogue_conversion_issues', function (Blueprint $t) {
            $t->id(); $t->foreignUuid('run_id')->constrained('catalogue_conversion_runs')->restrictOnDelete();
            $t->string('source_table', 64); $t->unsignedBigInteger('source_id'); $t->string('kind', 64);
            $t->json('source_values')->nullable(); $t->string('status', 32)->default('pending'); $t->timestamps();
            $t->unique(['run_id', 'source_table', 'source_id', 'kind'], 'catalogue_issue_source_unique');
        });
        Schema::create('catalogue_conversion_mappings', function (Blueprint $t) {
            $t->id(); $t->foreignUuid('run_id')->constrained('catalogue_conversion_runs')->restrictOnDelete();
            $t->string('source_table', 64); $t->unsignedBigInteger('source_id'); $t->string('purpose', 32);
            $t->string('target_table', 64); $t->unsignedBigInteger('target_id'); $t->json('source_values'); $t->timestamps();
            $t->unique(['source_table', 'source_id', 'purpose'], 'catalogue_mapping_source_unique');
        });
    }
    public function down(): void
    {
        if (DB::table('catalogue_conversion_runs')->exists()) { throw new RuntimeException('Conversion evidence must be preserved.'); }
        Schema::drop('catalogue_conversion_mappings'); Schema::drop('catalogue_conversion_issues'); Schema::drop('catalogue_conversion_runs');
    }
};
