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
        DB::unprepared(file_get_contents(database_path('sql/entity_sync_upgrade.sql')));
        DB::unprepared(file_get_contents(database_path('sql/entity_sync_functions.sql')));
        DB::unprepared(file_get_contents(database_path('sql/entity_sync_users.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement('LOCK TABLE public.users IN SHARE ROW EXCLUSIVE MODE');
        DB::statement('LOCK TABLE public.entity_changes IN ACCESS EXCLUSIVE MODE');
        if (DB::table('public.entity_changes')->where('entity', '!=', App\Models\User::class)->exists()) {
            throw new RuntimeException('Откат запрещён: журнал содержит другие сущности.');
        }
        DB::unprepared(<<<'SQL'
DROP TRIGGER wms_users_change ON public.users;
DROP TRIGGER wms_users_truncate ON public.users;
DROP FUNCTION wms.capture_entity_change();
DROP FUNCTION wms.capture_entity_truncate();
DROP FUNCTION wms.append_entity_change(text, text, text, text, jsonb);
DROP INDEX public.entity_changes_tenant_revision;
DROP INDEX public.entity_changes_record_revision;
ALTER TABLE public.entity_changes DROP COLUMN entity;
ALTER TABLE public.entity_changes ALTER COLUMN entity_id TYPE bigint USING entity_id::bigint;
ALTER TABLE public.entity_changes RENAME COLUMN entity_id TO user_id;
ALTER TABLE public.entity_changes RENAME TO user_changes;
ALTER TABLE public.user_changes SET SCHEMA wms;
CREATE INDEX user_changes_tenant_revision ON wms.user_changes (tenant_id, revision);
CREATE INDEX user_changes_user_revision ON wms.user_changes (user_id, revision DESC);
UPDATE wms.sync_state SET generation = md5(random()::text || clock_timestamp()::text);
SQL);
        $legacy = file_get_contents(database_path('sql/user_sync.sql'));
        DB::unprepared(mb_substr($legacy, mb_strpos($legacy, 'CREATE FUNCTION wms.capture_user_change()')));
    }
};
