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
        DB::unprepared(file_get_contents(database_path('sql/tenant_sync_upgrade.sql')));
        DB::unprepared(file_get_contents(database_path('sql/tenant_sync_append.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '60s'");
        DB::statement('LOCK TABLE public.sync_state IN ACCESS EXCLUSIVE MODE');
        DB::statement('LOCK TABLE public.entity_changes IN ACCESS EXCLUSIVE MODE');
        if (DB::table('public.entity_changes')->select('revision')->groupBy('revision')->havingRaw('count(*) > 1')->exists()) {
            throw new RuntimeException('Откат запрещён: ревизии разных организаций совпадают.');
        }
        DB::unprepared(<<<'SQL'
CREATE TABLE wms.sync_state (id smallint PRIMARY KEY CHECK (id = 1), revision bigint NOT NULL,
    generation varchar(32) NOT NULL DEFAULT md5(random()::text || clock_timestamp()::text));
INSERT INTO wms.sync_state (id, revision) SELECT 1, coalesce(max(revision), 0) FROM public.sync_state;
ALTER TABLE public.entity_changes DROP CONSTRAINT entity_changes_tenant_revision_unique;
ALTER TABLE public.entity_changes ADD CONSTRAINT user_changes_pkey PRIMARY KEY (revision);
DROP TABLE public.sync_state;
SQL);
        DB::unprepared(file_get_contents(database_path('sql/entity_sync_functions.sql')));
    }
};
