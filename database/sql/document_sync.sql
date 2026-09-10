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
        payload := payload || jsonb_build_object('id', new_row->>'id', 'client_id', new_row->>'client_id', 'doc_type_id', new_row->>'doc_type_id');
        PERFORM wms.append_entity_change('App\Models\Document', new_row->>'tenant_id', new_row->>'id', 'upsert', payload);
    END IF;
    RETURN NULL;
END;
$$;
LOCK TABLE clients.documents, clients.doc_types IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_documents_change AFTER INSERT OR UPDATE OR DELETE ON clients.documents FOR EACH ROW EXECUTE FUNCTION wms.capture_document_change();
CREATE TRIGGER wms_documents_truncate BEFORE TRUNCATE ON clients.documents FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Document');
CREATE TRIGGER wms_doc_types_change AFTER INSERT OR UPDATE OR DELETE ON clients.doc_types FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\DocType', '["name", "shortname", "status", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_doc_types_truncate BEFORE TRUNCATE ON clients.doc_types FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_global_entity_truncate('App\Models\DocType');
DO $$
DECLARE item record;
BEGIN
FOR item IN SELECT * FROM (SELECT 'App\Models\Document' AS entity, id::text AS id, tenant_id AS tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'client_id', client_id::text, 'doc_type_id', doc_type_id::text, 'status', status, 'comment', comment, 'internal_comment', internal_comment, 'src', src, 'doc_date', doc_date, 'accepted_at', accepted_at, 'payed_at', payed_at, 'canceled_at', canceled_at, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM clients.documents UNION ALL SELECT 'App\Models\DocType' AS entity, id::text AS id, NULL::text AS tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'status', status, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM clients.doc_types) rows ORDER BY tenant_id COLLATE "C" NULLS FIRST, entity, id COLLATE "C" LOOP
PERFORM wms.append_entity_change(item.entity, item.tenant_id, item.id, 'upsert', item.data);
END LOOP;
END;
$$;
