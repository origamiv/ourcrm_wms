<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '120s'");
        DB::unprepared(file_get_contents(database_path('sql/integration_sync.sql')));
    }

    public function down(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement('LOCK TABLE integration.webhooks, integration.data, integration.rules, integration.services, integration.type_hook, integration.type_processing IN SHARE ROW EXCLUSIVE MODE');
        foreach (['webhooks' => 'IntegrationWebhook', 'data' => 'IntegrationData', 'rules' => 'IntegrationRule', 'services' => 'IntegrationService', 'type_hook' => 'IntegrationHookType', 'type_processing' => 'IntegrationProcessingType'] as $table => $model) {
            DB::statement("DROP TRIGGER wms_integration_{$table}_change ON integration.{$table}");
            DB::statement("DROP TRIGGER wms_integration_{$table}_truncate ON integration.{$table}");
            $entity = 'App\\Models\\'.$model;
            DB::statement('UPDATE public.sync_state SET generation = md5(random()::text || clock_timestamp()::text) WHERE id IN (SELECT s.id FROM public.sync_state s JOIN public.entity_changes e ON e.tenant_id IS NOT DISTINCT FROM s.tenant_id WHERE e.entity = ?)', [$entity]);
            DB::table('public.entity_changes')->where('entity', $entity)->delete();
        }
    }
};
