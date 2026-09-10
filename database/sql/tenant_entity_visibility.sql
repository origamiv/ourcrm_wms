-- NULL owner: no assignments means public, otherwise explicit membership is required.
CREATE OR REPLACE FUNCTION wms.entity_visible(kind text, item text, owner text, viewer text)
RETURNS boolean LANGUAGE sql STABLE SET search_path = pg_catalog AS $$
    SELECT CASE WHEN owner IS NOT NULL THEN owner = viewer
    ELSE NOT EXISTS (SELECT 1 FROM main.tenant_entity te WHERE te.entity_type = kind AND te.entity_id::text = item)
        OR EXISTS (SELECT 1 FROM main.tenant_entity te WHERE te.entity_type = kind AND te.entity_id::text = item AND te.tenant_id = viewer) END;
$$;

CREATE OR REPLACE FUNCTION wms.shared_entity_visible(kind text, item text, viewer text)
RETURNS boolean LANGUAGE sql STABLE SET search_path = pg_catalog AS $$
    SELECT kind IN ('App\Models\Module', 'App\Models\Feature', 'App\Models\DocType')
        OR wms.entity_visible(kind, item, NULL, viewer);
$$;

-- Internal writer: the caller has already decided which tenant may receive the event.
CREATE OR REPLACE FUNCTION wms.write_entity_change(kind text, tenant text, item_id text, op text, payload jsonb)
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
REVOKE ALL ON FUNCTION wms.write_entity_change(text, text, text, text, jsonb) FROM PUBLIC;

CREATE OR REPLACE FUNCTION wms.append_entity_change(kind text, tenant text, item_id text, op text, payload jsonb DEFAULT NULL)
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE target record; allowed boolean;
BEGIN
    PERFORM wms.write_entity_change(kind, tenant, item_id, op, payload);
    IF tenant IS NULL THEN
        FOR target IN SELECT tenant_id FROM public.sync_state WHERE tenant_id IS NOT NULL ORDER BY tenant_id COLLATE "C" LOOP
            allowed := op = 'upsert' AND wms.shared_entity_visible(kind, item_id, target.tenant_id);
            PERFORM wms.write_entity_change(kind, target.tenant_id, item_id,
                CASE WHEN allowed THEN 'upsert' ELSE 'remove' END,
                CASE WHEN allowed THEN payload ELSE NULL END);
        END LOOP;
    END IF;
END;
$$;
REVOKE ALL ON FUNCTION wms.append_entity_change(text, text, text, text, jsonb) FROM PUBLIC;

-- Seed shared records only once for a new tenant, serialized with shared writes.
CREATE OR REPLACE FUNCTION wms.initialize_tenant_shares(viewer text)
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE item record; initialized boolean;
BEGIN
    IF viewer IS NULL THEN RETURN; END IF;
    INSERT INTO public.sync_state (tenant_id) VALUES (NULL) ON CONFLICT (tenant_id) DO NOTHING;
    PERFORM 1 FROM public.sync_state WHERE tenant_id IS NULL FOR UPDATE;
    INSERT INTO public.sync_state (tenant_id) VALUES (viewer) ON CONFLICT (tenant_id) DO NOTHING;
    SELECT shared_initialized INTO initialized FROM public.sync_state WHERE tenant_id = viewer FOR UPDATE;
    IF initialized THEN RETURN; END IF;
    FOR item IN SELECT * FROM (
        SELECT DISTINCT ON (entity, entity_id) * FROM public.entity_changes WHERE tenant_id IS NULL
        ORDER BY entity, entity_id, revision DESC
    ) latest WHERE operation = 'upsert' ORDER BY entity, entity_id LOOP
        IF wms.shared_entity_visible(item.entity, item.entity_id, viewer) THEN
            PERFORM wms.write_entity_change(item.entity, viewer, item.entity_id, 'upsert', item.data);
        END IF;
    END LOOP;
    UPDATE public.sync_state SET shared_initialized = true WHERE tenant_id = viewer;
END;
$$;
REVOKE ALL ON FUNCTION wms.initialize_tenant_shares(text) FROM PUBLIC;

CREATE OR REPLACE FUNCTION wms.refresh_entity_visibility(kind text, item_id text)
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE latest record; target record; allowed boolean;
BEGIN
    INSERT INTO public.sync_state (tenant_id) VALUES (NULL) ON CONFLICT (tenant_id) DO NOTHING;
    PERFORM 1 FROM public.sync_state WHERE tenant_id IS NULL FOR UPDATE;
    SELECT * INTO latest FROM public.entity_changes
        WHERE tenant_id IS NULL AND entity = kind AND entity_id = item_id
        ORDER BY revision DESC LIMIT 1;
    -- An owned record never uses tenant_entity, including former shared records.
    IF NOT FOUND OR latest.operation <> 'upsert' THEN RETURN; END IF;
    FOR target IN SELECT tenant_id FROM public.sync_state WHERE tenant_id IS NOT NULL ORDER BY tenant_id COLLATE "C" LOOP
        allowed := wms.shared_entity_visible(kind, item_id, target.tenant_id);
        PERFORM wms.write_entity_change(kind, target.tenant_id, item_id,
            CASE WHEN allowed THEN 'upsert' ELSE 'remove' END,
            CASE WHEN allowed THEN latest.data ELSE NULL END);
    END LOOP;
END;
$$;
REVOKE ALL ON FUNCTION wms.refresh_entity_visibility(text, text) FROM PUBLIC;

CREATE OR REPLACE FUNCTION wms.capture_tenant_entity_change()
RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF TG_OP <> 'INSERT' THEN
        PERFORM wms.refresh_entity_visibility(OLD.entity_type, OLD.entity_id::text);
    END IF;
    IF TG_OP = 'INSERT' OR (TG_OP = 'UPDATE' AND
        (OLD.entity_type IS DISTINCT FROM NEW.entity_type OR OLD.entity_id IS DISTINCT FROM NEW.entity_id)) THEN
        PERFORM wms.refresh_entity_visibility(NEW.entity_type, NEW.entity_id::text);
    END IF;
    RETURN NULL;
END;
$$;
CREATE OR REPLACE FUNCTION wms.capture_tenant_entity_truncate()
RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE item record;
BEGIN
    FOR item IN SELECT DISTINCT ON (entity, entity_id) entity, entity_id
        FROM public.entity_changes WHERE tenant_id IS NULL ORDER BY entity, entity_id LOOP
        PERFORM wms.refresh_entity_visibility(item.entity, item.entity_id);
    END LOOP;
    RETURN NULL;
END;
$$;
CREATE TRIGGER wms_tenant_entity_change AFTER INSERT OR UPDATE OR DELETE ON main.tenant_entity
FOR EACH ROW EXECUTE FUNCTION wms.capture_tenant_entity_change();
CREATE TRIGGER wms_tenant_entity_truncate AFTER TRUNCATE ON main.tenant_entity
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_tenant_entity_truncate();
