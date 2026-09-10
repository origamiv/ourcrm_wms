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
        DB::unprepared(file_get_contents(database_path('sql/client_party_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement('LOCK TABLE clients.companies, clients.individuals IN SHARE ROW EXCLUSIVE MODE');
        foreach (['companies' => App\Models\ClientCompany::class, 'individuals' => App\Models\ClientIndividual::class] as $table => $entity) {
            DB::unprepared("DROP TRIGGER wms_client_{$table}_change ON clients.{$table}; DROP TRIGGER wms_client_{$table}_truncate ON clients.{$table};");
            DB::update('UPDATE public.sync_state s SET generation = md5(random()::text || clock_timestamp()::text) WHERE EXISTS (SELECT 1 FROM public.entity_changes e WHERE e.entity = ? AND e.tenant_id IS NOT DISTINCT FROM s.tenant_id)', [$entity]);
            DB::table('public.entity_changes')->where('entity', $entity)->delete();
        }
    }
};
