<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wms.tswms_imports') && ! Schema::hasTable('wms.import_runs')) {
            DB::statement('ALTER TABLE wms.tswms_imports RENAME TO import_runs');
        }

        Schema::table('wms.import_runs', function (Blueprint $table): void {
            $table->string('project', 64)->default('tswms');
            $table->string('name', 255)->nullable();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedInteger('processed_chunks')->default(0);
        });

        DB::table('wms.import_runs')->update([
            'project' => DB::raw('source_system'),
            'name' => DB::raw("CASE WHEN options->'only' IS NULL OR jsonb_array_length(options->'only') = 0 THEN 'Полный импорт' ELSE (SELECT string_agg(value, ', ') FROM jsonb_array_elements_text(options->'only')) END"),
            'total_chunks' => DB::raw('total_jobs'),
            'processed_chunks' => DB::raw('completed_jobs'),
        ]);
    }

    public function down(): void
    {
        Schema::table('wms.import_runs', function (Blueprint $table): void {
            $table->dropColumn(['project', 'name', 'total_records', 'total_chunks', 'processed_records', 'processed_chunks']);
        });

        if (Schema::hasTable('wms.import_runs') && ! Schema::hasTable('wms.tswms_imports')) {
            DB::statement('ALTER TABLE wms.import_runs RENAME TO tswms_imports');
        }
    }
};
