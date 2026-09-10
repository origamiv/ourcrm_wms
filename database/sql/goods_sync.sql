CREATE OR REPLACE FUNCTION wms.capture_good_card_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE old_row jsonb; new_row jsonb; payload jsonb;
BEGIN
    IF TG_OP <> 'INSERT' THEN old_row := to_jsonb(OLD); END IF;
    IF TG_OP <> 'DELETE' THEN new_row := to_jsonb(NEW); END IF;
    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND (old_row->>'tenant_id' IS DISTINCT FROM new_row->>'tenant_id' OR old_row->>'id' IS DISTINCT FROM new_row->>'id')) THEN
        PERFORM wms.append_entity_change('App\Models\GoodCard', old_row->>'tenant_id', old_row->>'id', 'remove');
    END IF;
    IF TG_OP <> 'DELETE' THEN
        SELECT jsonb_object_agg(field, new_row->field) INTO payload FROM jsonb_array_elements_text('["name","status","tenant_id","created_at","updated_at","deleted_at"]'::jsonb) fields(field);
        payload := payload || jsonb_build_object('id', new_row->>'id', 'good_id', new_row->>'good_id');
        PERFORM wms.append_entity_change('App\Models\GoodCard', new_row->>'tenant_id', new_row->>'id', 'upsert', payload);
    END IF;
    RETURN NULL;
END;
$$;
LOCK TABLE goods.goods, goods.good_cards, goods.type_goods, goods.unit_goods IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_goods_change AFTER INSERT OR UPDATE OR DELETE ON goods.goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Good', '["name", "shortname", "code", "parent_id", "parent_code", "type_good", "type_unit", "barcodes", "articul", "is_from_external", "is_category", "level", "goodcard_id", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_goods_truncate BEFORE TRUNCATE ON goods.goods FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Good');
CREATE TRIGGER wms_good_cards_change AFTER INSERT OR UPDATE OR DELETE ON goods.good_cards FOR EACH ROW EXECUTE FUNCTION wms.capture_good_card_change();
CREATE TRIGGER wms_good_cards_truncate BEFORE TRUNCATE ON goods.good_cards FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\GoodCard');
CREATE TRIGGER wms_type_goods_change AFTER INSERT OR UPDATE OR DELETE ON goods.type_goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\GoodType', '["name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_type_goods_truncate BEFORE TRUNCATE ON goods.type_goods FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\GoodType');
CREATE TRIGGER wms_unit_goods_change AFTER INSERT OR UPDATE OR DELETE ON goods.unit_goods FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\GoodUnit', '["name", "shortname", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_unit_goods_truncate BEFORE TRUNCATE ON goods.unit_goods FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\GoodUnit');
DO $$
DECLARE item record;
BEGIN
FOR item IN SELECT * FROM (SELECT 'App\Models\Good' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'code', code, 'parent_id', parent_id, 'parent_code', parent_code, 'type_good', type_good, 'type_unit', type_unit, 'barcodes', barcodes, 'articul', articul, 'is_from_external', is_from_external, 'is_category', is_category, 'level', level, 'goodcard_id', goodcard_id, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM goods.goods UNION ALL SELECT 'App\Models\GoodCard' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'good_id', good_id::text, 'name', name, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM goods.good_cards UNION ALL SELECT 'App\Models\GoodType' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM goods.type_goods UNION ALL SELECT 'App\Models\GoodUnit' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM goods.unit_goods) rows ORDER BY tenant_id COLLATE "C" NULLS FIRST, entity, id COLLATE "C" LOOP
PERFORM wms.append_entity_change(item.entity, item.tenant_id, item.id, 'upsert', item.data);
END LOOP;
END;
$$;
