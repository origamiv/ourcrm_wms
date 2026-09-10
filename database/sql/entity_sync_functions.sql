CREATE OR REPLACE FUNCTION wms.append_entity_change(kind text, tenant text, item_id text, op text, payload jsonb DEFAULT NULL)
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE rev bigint;
BEGIN
    UPDATE wms.sync_state SET revision = revision + 1 WHERE id = 1 RETURNING revision INTO rev;
    INSERT INTO public.entity_changes (revision, tenant_id, entity, entity_id, operation, data)
    VALUES (rev, tenant, kind, item_id, op, payload);
END;
$$;
CREATE OR REPLACE FUNCTION wms.capture_entity_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE old_row jsonb; new_row jsonb; payload jsonb;
BEGIN
    IF TG_OP <> 'INSERT' THEN old_row := to_jsonb(OLD); END IF;
    IF TG_OP <> 'DELETE' THEN new_row := to_jsonb(NEW); END IF;
    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND
        (old_row->>'tenant_id' IS DISTINCT FROM new_row->>'tenant_id' OR old_row->>'id' IS DISTINCT FROM new_row->>'id')) THEN
        PERFORM wms.append_entity_change(TG_ARGV[0], old_row->>'tenant_id', old_row->>'id', 'remove');
    END IF;
    IF TG_OP <> 'DELETE' THEN
        SELECT jsonb_object_agg(field, new_row->field) INTO payload FROM jsonb_array_elements_text(TG_ARGV[1]::jsonb) AS fields(field);
        payload := coalesce(payload, '{}'::jsonb) || jsonb_build_object('id', new_row->>'id');
        PERFORM wms.append_entity_change(TG_ARGV[0], new_row->>'tenant_id', new_row->>'id', 'upsert', payload);
    END IF;
    RETURN NULL;
END;
$$;
CREATE OR REPLACE FUNCTION wms.capture_entity_truncate() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE item record;
BEGIN
    FOR item IN EXECUTE format('SELECT id::text AS id, tenant_id::text AS tenant_id FROM %I.%I ORDER BY id::text COLLATE "C"', TG_TABLE_SCHEMA, TG_TABLE_NAME) LOOP
        PERFORM wms.append_entity_change(TG_ARGV[0], item.tenant_id, item.id, 'remove');
    END LOOP;
    RETURN NULL;
END;
$$;
-- Вызывать запись событий разрешено только через установленные триггеры владельца.
REVOKE ALL ON FUNCTION wms.append_entity_change(text, text, text, text, jsonb) FROM PUBLIC;
