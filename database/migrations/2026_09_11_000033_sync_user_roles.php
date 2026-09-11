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
        DB::unprepared(file_get_contents(database_path('sql/user_roles_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS wms_role_user_change ON main.role_user;
DROP TRIGGER IF EXISTS wms_users_change ON public.users;
DROP FUNCTION IF EXISTS wms.capture_role_user_change();
DROP FUNCTION IF EXISTS wms.capture_user_with_roles_change();
DROP FUNCTION IF EXISTS wms.user_payload_with_roles(bigint);
DROP FUNCTION IF EXISTS wms.user_roles_payload(bigint, text);
SQL);
    }
};
