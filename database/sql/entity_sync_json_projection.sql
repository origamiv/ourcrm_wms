CREATE OR REPLACE FUNCTION wms.capture_entity_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE old_row jsonb; new_row jsonb; payload jsonb; projection record; nested jsonb;
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
        IF TG_NARGS > 2 THEN
            FOR projection IN SELECT key, value FROM jsonb_each(TG_ARGV[2]::jsonb) LOOP
                SELECT jsonb_object_agg(field, new_row->projection.key->field) INTO nested
                FROM jsonb_array_elements_text(projection.value) AS fields(field);
                payload := payload || jsonb_build_object(projection.key, coalesce(nested, '{}'::jsonb));
            END LOOP;
        END IF;
        PERFORM wms.append_entity_change(TG_ARGV[0], new_row->>'tenant_id', new_row->>'id', 'upsert', payload);
    END IF;
    RETURN NULL;
END;
$$;
