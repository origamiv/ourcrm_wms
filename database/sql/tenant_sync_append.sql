CREATE OR REPLACE FUNCTION wms.append_entity_change(kind text, tenant text, item_id text, op text, payload jsonb DEFAULT NULL)
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE rev bigint;
BEGIN
    INSERT INTO public.sync_state (tenant_id, revision) VALUES (tenant, 1)
    ON CONFLICT (tenant_id) DO UPDATE SET revision = public.sync_state.revision + 1
    RETURNING revision INTO rev;
    INSERT INTO public.entity_changes (revision, tenant_id, entity, entity_id, operation, data)
    VALUES (rev, tenant, kind, item_id, op, payload);
END;
$$;
REVOKE ALL ON FUNCTION wms.append_entity_change(text, text, text, text, jsonb) FROM PUBLIC;
