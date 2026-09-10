<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '60s'");
        DB::unprepared(file_get_contents(database_path('sql/permission_role_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::unprepared(<<<'SQL'
LOCK TABLE main.permission_role IN SHARE ROW EXCLUSIVE MODE;
DROP TRIGGER wms_permission_roles_change ON main.permission_role;
DROP TRIGGER wms_permission_roles_truncate ON main.permission_role;
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text)
WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = 'App\Models\PermissionRole' AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity = 'App\Models\PermissionRole';
SQL);
    }
};
