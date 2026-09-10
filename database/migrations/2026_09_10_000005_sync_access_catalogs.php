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
        DB::unprepared(file_get_contents(database_path('sql/access_catalog_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::unprepared(<<<'SQL'
LOCK TABLE main.roles, main.permissions IN SHARE ROW EXCLUSIVE MODE;
DROP TRIGGER wms_roles_change ON main.roles;
DROP TRIGGER wms_roles_truncate ON main.roles;
DROP TRIGGER wms_permissions_change ON main.permissions;
DROP TRIGGER wms_permissions_truncate ON main.permissions;
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text)
WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity IN ('App\Models\Role', 'App\Models\Permission') AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity IN ('App\Models\Role', 'App\Models\Permission');
SQL);
    }
};
