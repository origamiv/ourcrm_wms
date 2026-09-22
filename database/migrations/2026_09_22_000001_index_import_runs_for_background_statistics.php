<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS import_runs_tenant_webhook_created_idx ON wms.import_runs (tenant_id, source_webhook_id, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS wms.import_runs_tenant_webhook_created_idx');
    }
};
