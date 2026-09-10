CREATE OR REPLACE FUNCTION wms.capture_global_entity_truncate() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE item record;
BEGIN
    FOR item IN EXECUTE format('SELECT id::text AS id FROM %I.%I ORDER BY id::text COLLATE "C"', TG_TABLE_SCHEMA, TG_TABLE_NAME) LOOP
        PERFORM wms.append_entity_change(TG_ARGV[0], NULL, item.id, 'remove');
    END LOOP;
    RETURN NULL;
END;
$$;
LOCK TABLE main.modules, main.features, main.icons, main.files IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_modules_change AFTER INSERT OR UPDATE OR DELETE ON main.modules FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Module', '["name", "shortname", "descr", "fn", "domain", "status", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_modules_truncate BEFORE TRUNCATE ON main.modules FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_global_entity_truncate('App\Models\Module');
CREATE TRIGGER wms_features_change AFTER INSERT OR UPDATE OR DELETE ON main.features FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Feature', '["name", "shortname", "is_resource", "module_id", "status", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_features_truncate BEFORE TRUNCATE ON main.features FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_global_entity_truncate('App\Models\Feature');
CREATE TRIGGER wms_icons_change AFTER INSERT OR UPDATE OR DELETE ON main.icons FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Icon', '["name", "path", "category", "size", "ext", "user_id", "company_id", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_icons_truncate BEFORE TRUNCATE ON main.icons FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Icon');
CREATE TRIGGER wms_files_change AFTER INSERT OR UPDATE OR DELETE ON main.files FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\File', '["name", "path", "category", "size", "ext", "user_id", "company_id", "is_s3", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_files_truncate BEFORE TRUNCATE ON main.files FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\File');
DO $$
DECLARE item record;
BEGIN
    FOR item IN SELECT * FROM (SELECT 'App\Models\Module' AS entity, id::text AS id, NULL::text AS tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'descr', descr, 'fn', fn, 'domain', domain, 'status', status, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM main.modules UNION ALL SELECT 'App\Models\Feature' AS entity, id::text AS id, NULL::text AS tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'is_resource', is_resource, 'module_id', module_id, 'status', status, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM main.features UNION ALL SELECT 'App\Models\Icon' AS entity, id::text AS id, tenant_id AS tenant_id, jsonb_build_object('id', id::text, 'name', name, 'path', path, 'category', category, 'size', size, 'ext', ext, 'user_id', user_id, 'company_id', company_id, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM main.icons UNION ALL SELECT 'App\Models\File' AS entity, id::text AS id, tenant_id AS tenant_id, jsonb_build_object('id', id::text, 'name', name, 'path', path, 'category', category, 'size', size, 'ext', ext, 'user_id', user_id, 'company_id', company_id, 'is_s3', is_s3, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM main.files) catalogs ORDER BY tenant_id COLLATE "C" NULLS FIRST, entity, id COLLATE "C"
    LOOP
        PERFORM wms.append_entity_change(item.entity, item.tenant_id, item.id, 'upsert', item.data);
    END LOOP;
END;
$$;
