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
        DB::unprepared(file_get_contents(database_path('sql/entity_sync_json_projection.sql')));
        DB::unprepared(file_get_contents(database_path('sql/company_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::unprepared(<<<'SQL'
LOCK TABLE main.companies, main.company_contacts IN SHARE ROW EXCLUSIVE MODE;
DROP TRIGGER wms_companies_change ON main.companies;
DROP TRIGGER wms_companies_truncate ON main.companies;
DROP TRIGGER wms_company_contacts_change ON main.company_contacts;
DROP TRIGGER wms_company_contacts_truncate ON main.company_contacts;
UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text)
WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity IN ('App\Models\Company', 'App\Models\CompanyContact') AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id);
DELETE FROM public.entity_changes WHERE entity IN ('App\Models\Company', 'App\Models\CompanyContact');
SQL);
    }
};
