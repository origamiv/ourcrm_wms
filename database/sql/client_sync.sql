LOCK TABLE clients.clients IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_clients_change AFTER INSERT OR UPDATE OR DELETE ON clients.clients
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Client', '["name","shortname","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_clients_truncate BEFORE TRUNCATE ON clients.clients
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Client');
DO $$
DECLARE item record;
BEGIN
    FOR item IN SELECT id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data
        FROM clients.clients ORDER BY tenant_id COLLATE "C" NULLS FIRST, id::text COLLATE "C"
    LOOP
        PERFORM wms.append_entity_change('App\Models\Client', item.tenant_id, item.id, 'upsert', item.data);
    END LOOP;
END;
$$;
