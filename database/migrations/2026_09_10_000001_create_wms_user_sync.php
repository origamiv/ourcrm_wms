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
        DB::unprepared(file_get_contents(database_path('sql/user_sync.sql')));
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS wms_users_change ON public.users; DROP TRIGGER IF EXISTS wms_users_truncate ON public.users; DROP FUNCTION IF EXISTS wms.capture_user_change(); DROP FUNCTION IF EXISTS wms.capture_users_truncate(); DROP FUNCTION IF EXISTS wms.user_payload(public.users); DROP TABLE IF EXISTS wms.user_changes; DROP TABLE IF EXISTS wms.sync_state; DROP TABLE IF EXISTS wms.personal_access_tokens;');
    }
};
