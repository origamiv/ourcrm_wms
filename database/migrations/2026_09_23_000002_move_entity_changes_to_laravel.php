<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<string> */
    private array $writerFunctions = [
        'append_entity_change',
        'write_entity_change',
        'refresh_entity_visibility',
        'initialize_tenant_shares',
        'shared_entity_visible',
        'capture_entity_change',
        'capture_entity_truncate',
        'capture_global_entity_truncate',
        'capture_document_change',
        'capture_good_card_change',
        'capture_user_change',
        'capture_user_with_roles_change',
        'capture_users_truncate',
        'capture_role_user_change',
        'capture_tenant_entity_change',
        'capture_tenant_entity_truncate',
        'user_payload',
        'user_payload_with_roles',
        'user_roles_payload',
        'service_changed',
    ];

    public function up(): void
    {
        DB::statement("SET LOCAL lock_timeout = '5s'");
        DB::statement("SET LOCAL statement_timeout = '120s'");

        $triggers = DB::table('pg_trigger as trigger')
            ->join('pg_proc as procedure', 'procedure.oid', '=', 'trigger.tgfoid')
            ->join('pg_class as relation', 'relation.oid', '=', 'trigger.tgrelid')
            ->join('pg_namespace as namespace', 'namespace.oid', '=', 'relation.relnamespace')
            ->where('trigger.tgisinternal', false)
            ->whereIn('procedure.proname', $this->writerFunctions)
            ->get(['namespace.nspname as schema', 'relation.relname as table', 'trigger.tgname as trigger']);

        foreach ($triggers as $trigger) {
            DB::statement(sprintf(
                'DROP TRIGGER %s ON %s.%s',
                $this->quote($trigger->trigger),
                $this->quote($trigger->schema),
                $this->quote($trigger->table),
            ));
        }

        foreach ([
            'shared.service_changed()',
            'wms.capture_tenant_entity_truncate()',
            'wms.capture_tenant_entity_change()',
            'wms.capture_role_user_change()',
            'wms.capture_user_with_roles_change()',
            'wms.capture_users_truncate()',
            'wms.capture_user_change()',
            'wms.capture_good_card_change()',
            'wms.capture_document_change()',
            'wms.capture_global_entity_truncate()',
            'wms.capture_entity_truncate()',
            'wms.capture_entity_change()',
            'wms.user_payload_with_roles(bigint)',
            'wms.user_roles_payload(bigint, text)',
            'wms.user_payload(public.users)',
            'wms.refresh_entity_visibility(text, text)',
            'wms.initialize_tenant_shares(text)',
            'wms.append_entity_change(text, text, text, text, jsonb)',
            'wms.write_entity_change(text, text, text, text, jsonb)',
            'wms.shared_entity_visible(text, text, text)',
        ] as $function) {
            DB::statement("DROP FUNCTION IF EXISTS {$function}");
        }
    }

    public function down(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/entity_sync_trigger_rollback.sql')));

        foreach (config('sync.entities') as $definition) {
            $entity = $definition['entity'];
            $table = $definition['table'];
            if ($entity === App\Models\User::class || in_array($table, ['clients.services', 'integration.services'], true)) {
                continue;
            }
            $relation = DB::selectOne('SELECT c.relkind FROM pg_class c WHERE c.oid = to_regclass(?)', [$table]);
            if (! $relation || ! in_array($relation->relkind, ['r', 'p'], true)) {
                continue;
            }
            [$schema, $name] = explode('.', $table, 2);
            $prefix = 'wms_'.$schema.'_'.$name;
            $arguments = [$entity, json_encode($definition['fields'], JSON_THROW_ON_ERROR)];
            if (isset($definition['json_fields'])) {
                $arguments[] = json_encode($definition['json_fields'], JSON_THROW_ON_ERROR);
            }
            $sqlArguments = implode(', ', array_map(fn (string $value): string => $this->literal($value), $arguments));
            DB::statement(sprintf(
                'CREATE TRIGGER %s AFTER INSERT OR UPDATE OR DELETE ON %s.%s FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change(%s)',
                $this->quote($prefix.'_change'),
                $this->quote($schema),
                $this->quote($name),
                $sqlArguments,
            ));
            $truncateFunction = ($definition['global'] ?? false) || ! in_array('tenant_id', $definition['fields'], true)
                ? 'capture_global_entity_truncate'
                : 'capture_entity_truncate';
            DB::statement(sprintf(
                'CREATE TRIGGER %s BEFORE TRUNCATE ON %s.%s FOR EACH STATEMENT EXECUTE FUNCTION wms.%s(%s)',
                $this->quote($prefix.'_truncate'),
                $this->quote($schema),
                $this->quote($name),
                $truncateFunction,
                $this->literal($entity),
            ));
        }

        DB::statement('CREATE TRIGGER wms_users_change AFTER INSERT OR UPDATE OR DELETE ON public.users FOR EACH ROW EXECUTE FUNCTION wms.capture_user_with_roles_change()');
        DB::statement("CREATE TRIGGER wms_users_truncate BEFORE TRUNCATE ON public.users FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\\Models\\User')");
        DB::statement('CREATE TRIGGER wms_role_user_change AFTER INSERT OR UPDATE OR DELETE ON main.role_user FOR EACH ROW EXECUTE FUNCTION wms.capture_role_user_change()');
        if (DB::selectOne("SELECT to_regclass('shared.services') AS relation")->relation !== null) {
            DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION shared.service_changed() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE kind text; item_id text; item_tenant text; row_data jsonb;
BEGIN
    IF TG_OP = 'INSERT' THEN
        kind := CASE NEW.source_schema || '.' || NEW.source_table WHEN 'clients.services' THEN 'App\Models\ClientService' WHEN 'integration.services' THEN 'App\Models\IntegrationService' ELSE 'App\Models\SharedService' END;
    ELSE
        kind := CASE OLD.source_schema || '.' || OLD.source_table WHEN 'clients.services' THEN 'App\Models\ClientService' WHEN 'integration.services' THEN 'App\Models\IntegrationService' ELSE 'App\Models\SharedService' END;
        item_id := OLD.id::text; item_tenant := OLD.tenant_id;
    END IF;
    IF TG_OP = 'DELETE' THEN PERFORM wms.append_entity_change(kind, item_tenant, item_id, 'remove'); RETURN OLD; END IF;
    row_data := to_jsonb(NEW) - 'source_schema' - 'source_table' - 'source_id';
    PERFORM wms.append_entity_change(kind, NEW.tenant_id, NEW.id::text, 'upsert', row_data);
    RETURN NEW;
END;
$$;
SQL);
            DB::statement('CREATE TRIGGER shared_services_changed AFTER INSERT OR UPDATE OR DELETE ON shared.services FOR EACH ROW EXECUTE FUNCTION shared.service_changed()');
        }
    }

    private function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function literal(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
};
