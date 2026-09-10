<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::unprepared(<<<'SQL'
DO $$
BEGIN
    IF to_regclass('public.personal_access_tokens') IS NULL THEN
        RAISE EXCEPTION 'Required table public.personal_access_tokens is missing';
    END IF;
    IF to_regclass('wms.personal_access_tokens') IS NOT NULL THEN
        LOCK TABLE wms.personal_access_tokens IN ACCESS EXCLUSIVE MODE;
        IF EXISTS (SELECT 1 FROM wms.personal_access_tokens) THEN
            RAISE EXCEPTION 'Legacy WMS tokens must be migrated before removing their table';
        END IF;
        DROP TABLE wms.personal_access_tokens;
    END IF;
END;
$$;
SQL);
    }

    public function down(): void
    {
        // Общая таблица и её токены не принадлежат миграциям WMS.
    }
};
