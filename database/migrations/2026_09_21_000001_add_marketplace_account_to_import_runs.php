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
        Schema::table('wms.import_runs', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_account_id')->nullable()->after('source_webhook_id');
            $table->index(['tenant_id', 'source_account_id'], 'import_runs_marketplace_account_idx');
        });

        DB::statement("CREATE UNIQUE INDEX import_runs_marketplace_active_account_unique
            ON wms.import_runs (tenant_id, source_account_id)
            WHERE project = 'marketplace'
              AND source_account_id IS NOT NULL
              AND status IN ('queued', 'running')");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS import_runs_marketplace_active_account_unique');
        Schema::table('wms.import_runs', function (Blueprint $table): void {
            $table->dropIndex('import_runs_marketplace_account_idx');
            $table->dropColumn('source_account_id');
        });
    }
};
