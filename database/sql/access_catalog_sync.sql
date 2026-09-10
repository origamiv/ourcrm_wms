LOCK TABLE main.roles, main.permissions IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_roles_change AFTER INSERT OR UPDATE OR DELETE ON main.roles
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Role', '["name","slug","description","system","tags","status","company_id","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_roles_truncate BEFORE TRUNCATE ON main.roles
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Role');
CREATE TRIGGER wms_permissions_change AFTER INSERT OR UPDATE OR DELETE ON main.permissions
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Permission', '["name","slug","resource","system","status","module_id","feature_id","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_permissions_truncate BEFORE TRUNCATE ON main.permissions
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Permission');
DO $$
DECLARE item record;
BEGIN
    FOR item IN
        SELECT * FROM (SELECT 'App\Models\Role' AS entity, id::text AS id, tenant_id,
            jsonb_build_object('id', id::text, 'name', name, 'slug', slug, 'description', description, 'system', system,
                'tags', tags, 'status', status, 'company_id', company_id, 'tenant_id', tenant_id,
                'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data
        FROM main.roles
        UNION ALL
        SELECT 'App\Models\Permission', id::text, tenant_id,
            jsonb_build_object('id', id::text, 'name', name, 'slug', slug, 'resource', resource, 'system', system,
                'status', status, 'module_id', module_id, 'feature_id', feature_id, 'tenant_id', tenant_id,
                'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at)
        FROM main.permissions) catalogs
        ORDER BY tenant_id COLLATE "C" NULLS FIRST, entity, id
    LOOP
        PERFORM wms.append_entity_change(item.entity, item.tenant_id, item.id, 'upsert', item.data);
    END LOOP;
END;
$$;
