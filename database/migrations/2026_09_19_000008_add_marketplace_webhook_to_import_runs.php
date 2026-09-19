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
            $table->unsignedBigInteger('source_webhook_id')->nullable()->after('source_client_id');
            $table->index(['tenant_id', 'source_webhook_id'], 'import_runs_marketplace_webhook_idx');
        });

        DB::statement("CREATE UNIQUE INDEX import_runs_marketplace_active_unique ON wms.import_runs (tenant_id, source_webhook_id) WHERE project = 'marketplace' AND source_webhook_id IS NOT NULL AND status IN ('queued', 'running')");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS import_runs_marketplace_active_unique');
        Schema::table('wms.import_runs', function (Blueprint $table): void {
            $table->dropIndex('import_runs_marketplace_webhook_idx');
            $table->dropColumn('source_webhook_id');
        });
    }
};
