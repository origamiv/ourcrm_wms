<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX wms_acceptances_client_task_unique ON wms.acceptances (tenant_id, client_id, task_id) WHERE client_id IS NOT NULL AND task_id IS NOT NULL AND deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS wms_acceptances_client_task_unique');
    }
};
