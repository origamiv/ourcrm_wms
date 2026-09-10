CREATE OR REPLACE FUNCTION wms.capture_document_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE old_row jsonb; new_row jsonb; payload jsonb;
BEGIN
    IF TG_OP <> 'INSERT' THEN old_row := to_jsonb(OLD); END IF;
    IF TG_OP <> 'DELETE' THEN new_row := to_jsonb(NEW); END IF;
    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND (old_row->>'tenant_id' IS DISTINCT FROM new_row->>'tenant_id' OR old_row->>'id' IS DISTINCT FROM new_row->>'id')) THEN
        PERFORM wms.append_entity_change('App\Models\Document', old_row->>'tenant_id', old_row->>'id', 'remove');
    END IF;
    IF TG_OP <> 'DELETE' THEN
        SELECT jsonb_object_agg(field, new_row->field) INTO payload FROM jsonb_array_elements_text('["name","shortname","status","comment","internal_comment","src","doc_date","accepted_at","payed_at","canceled_at","tenant_id","created_at","updated_at","deleted_at"]'::jsonb) fields(field);
        payload := payload || jsonb_build_object('id', new_row->>'id', 'client_id', new_row->>'client_id', 'doc_type_id', new_row->>'doc_type_id', 'executor_id', new_row->>'executor_id', 'customer_id', new_row->>'customer_id', 'amount', new_row->>'amount');
        PERFORM wms.append_entity_change('App\Models\Document', new_row->>'tenant_id', new_row->>'id', 'upsert', payload);
    END IF;
    RETURN NULL;
END;
$$;
