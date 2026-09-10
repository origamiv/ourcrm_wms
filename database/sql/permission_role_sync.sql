LOCK TABLE main.permission_role IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_permission_roles_change AFTER INSERT OR UPDATE OR DELETE ON main.permission_role
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\PermissionRole', '["role_id","permission_id","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_permission_roles_truncate BEFORE TRUNCATE ON main.permission_role
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\PermissionRole');
DO $$
DECLARE item record;
BEGIN
    FOR item IN SELECT * FROM main.permission_role ORDER BY tenant_id COLLATE "C" NULLS FIRST, id LOOP
        PERFORM wms.append_entity_change('App\Models\PermissionRole', item.tenant_id, item.id::text, 'upsert',
            jsonb_build_object('id', item.id::text, 'role_id', item.role_id, 'permission_id', item.permission_id,
                'status', item.status, 'tenant_id', item.tenant_id, 'created_at', item.created_at,
                'updated_at', item.updated_at, 'deleted_at', item.deleted_at));
    END LOOP;
END;
$$;
