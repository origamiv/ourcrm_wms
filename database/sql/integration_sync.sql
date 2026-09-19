LOCK TABLE integration.webhooks, integration.data, integration.rules, integration.services, integration.type_hook, integration.type_processing IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_integration_webhooks_change AFTER INSERT OR UPDATE OR DELETE ON integration.webhooks FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\IntegrationWebhook', '["id", "name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at", "client_id", "service_id", "type_hook_id", "rules_id", "cnt", "dat_last_run"]');
CREATE TRIGGER wms_integration_webhooks_truncate BEFORE TRUNCATE ON integration.webhooks FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\IntegrationWebhook');
CREATE TRIGGER wms_integration_data_change AFTER INSERT OR UPDATE OR DELETE ON integration.data FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\IntegrationData', '["id", "name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at", "webhook_id", "service_id", "status_processing"]');
CREATE TRIGGER wms_integration_data_truncate BEFORE TRUNCATE ON integration.data FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\IntegrationData');
CREATE TRIGGER wms_integration_rules_change AFTER INSERT OR UPDATE OR DELETE ON integration.rules FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\IntegrationRule', '["id", "name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at", "type_processing_id"]');
CREATE TRIGGER wms_integration_rules_truncate BEFORE TRUNCATE ON integration.rules FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\IntegrationRule');
CREATE TRIGGER wms_integration_services_change AFTER INSERT OR UPDATE OR DELETE ON integration.services FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\IntegrationService', '["id", "name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_integration_services_truncate BEFORE TRUNCATE ON integration.services FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\IntegrationService');
CREATE TRIGGER wms_integration_type_hook_change AFTER INSERT OR UPDATE OR DELETE ON integration.type_hook FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\IntegrationHookType', '["id", "name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_integration_type_hook_truncate BEFORE TRUNCATE ON integration.type_hook FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\IntegrationHookType');
CREATE TRIGGER wms_integration_type_processing_change AFTER INSERT OR UPDATE OR DELETE ON integration.type_processing FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\IntegrationProcessingType', '["id", "name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_integration_type_processing_truncate BEFORE TRUNCATE ON integration.type_processing FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\IntegrationProcessingType');
-- Счётчик ревизий обновляется один раз на организацию: построчные UPDATE
-- одной строки в большой транзакции создают длинную цепочку версий PostgreSQL.
LOCK TABLE public.sync_state IN SHARE ROW EXCLUSIVE MODE;
CREATE TEMP TABLE wms_integration_seed ON COMMIT DROP AS
SELECT 'App\Models\IntegrationWebhook' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at, 'client_id', client_id, 'service_id', service_id, 'type_hook_id', type_hook_id, 'rules_id', rules_id, 'cnt', cnt, 'dat_last_run', dat_last_run) AS data FROM integration.webhooks UNION ALL SELECT 'App\Models\IntegrationData' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at, 'webhook_id', webhook_id, 'service_id', service_id, 'status_processing', status_processing) AS data FROM integration.data UNION ALL SELECT 'App\Models\IntegrationRule' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at, 'type_processing_id', type_processing_id) AS data FROM integration.rules UNION ALL SELECT 'App\Models\IntegrationService' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM integration.services UNION ALL SELECT 'App\Models\IntegrationHookType' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM integration.type_hook UNION ALL SELECT 'App\Models\IntegrationProcessingType' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM integration.type_processing;
INSERT INTO public.sync_state (tenant_id)
SELECT DISTINCT tenant_id FROM wms_integration_seed
ON CONFLICT (tenant_id) DO NOTHING;
DO $$
DECLARE target record; amount bigint; base_revision bigint;
BEGIN
    FOR target IN SELECT id, tenant_id FROM public.sync_state ORDER BY tenant_id COLLATE "C" NULLS FIRST LOOP
        SELECT count(*) INTO amount FROM wms_integration_seed seed
        WHERE seed.tenant_id IS NOT DISTINCT FROM target.tenant_id
            OR (seed.tenant_id IS NULL AND target.tenant_id IS NOT NULL);
        IF amount = 0 THEN CONTINUE; END IF;
        UPDATE public.sync_state SET revision = revision + amount WHERE id = target.id
        RETURNING revision - amount INTO base_revision;
        INSERT INTO public.entity_changes (revision, tenant_id, entity, entity_id, operation, data)
        SELECT base_revision + row_number() OVER (ORDER BY entity, id COLLATE "C"),
            target.tenant_id, entity, id,
            CASE WHEN allowed THEN 'upsert' ELSE 'remove' END,
            CASE WHEN allowed THEN data ELSE NULL END
        FROM (
            SELECT seed.*, (seed.tenant_id IS NOT NULL OR target.tenant_id IS NULL
                OR wms.shared_entity_visible(seed.entity, seed.id, target.tenant_id)) AS allowed
            FROM wms_integration_seed seed
            WHERE seed.tenant_id IS NOT DISTINCT FROM target.tenant_id
                OR (seed.tenant_id IS NULL AND target.tenant_id IS NOT NULL)
        ) projected;
    END LOOP;
END;
$$;
DROP TABLE pg_temp.wms_integration_seed;
