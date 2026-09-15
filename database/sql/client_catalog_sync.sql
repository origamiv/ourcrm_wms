LOCK TABLE clients.services, clients.accounts IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_client_services_change AFTER INSERT OR UPDATE OR DELETE ON clients.services FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\ClientService', '["name","shortname","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_client_services_truncate BEFORE TRUNCATE ON clients.services FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\ClientService');
CREATE TRIGGER wms_client_accounts_change AFTER INSERT OR UPDATE OR DELETE ON clients.accounts FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\ClientAccount', '["name","shortname","host","login","pass","token","descr","status","group_id","server_id","src","client_id","service_id","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_client_accounts_truncate BEFORE TRUNCATE ON clients.accounts FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\ClientAccount');
DO $$
DECLARE item record;
BEGIN
    FOR item IN SELECT id::text AS id, tenant_id, entity, data FROM (
        SELECT id, tenant_id, 'App\Models\ClientService' AS entity, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM clients.services
        UNION ALL
        SELECT id, tenant_id, 'App\Models\ClientAccount', jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'host', host, 'login', login, 'pass', pass, 'token', token, 'descr', descr, 'status', status, 'group_id', group_id, 'server_id', server_id, 'src', src, 'client_id', client_id, 'service_id', service_id, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) FROM clients.accounts
    ) rows ORDER BY tenant_id COLLATE "C", entity, id::text COLLATE "C"
    LOOP
        PERFORM wms.append_entity_change(item.entity, item.tenant_id, item.id, 'upsert', item.data);
    END LOOP;
END;
$$;
