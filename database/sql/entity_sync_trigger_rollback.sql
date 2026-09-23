-- Снимок прежнего механизма для отката миграции PHP-writer.
CREATE OR REPLACE FUNCTION wms.shared_entity_visible(kind text, item text, viewer text)
RETURNS boolean LANGUAGE sql STABLE SET search_path = pg_catalog AS $$
    SELECT kind IN ('App\Models\Module', 'App\Models\Feature', 'App\Models\DocType')
        OR wms.entity_visible(kind, item, NULL, viewer);
$$;

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

CREATE OR REPLACE FUNCTION wms.refresh_entity_visibility(kind text, item_id text)
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE latest record; target record; allowed boolean;
BEGIN
    INSERT INTO public.sync_state (tenant_id) VALUES (NULL) ON CONFLICT (tenant_id) DO NOTHING;
    PERFORM 1 FROM public.sync_state WHERE tenant_id IS NULL FOR UPDATE;
    SELECT * INTO latest FROM public.entity_changes WHERE tenant_id IS NULL AND entity = kind AND entity_id = item_id ORDER BY revision DESC LIMIT 1;
    IF NOT FOUND OR latest.operation <> 'upsert' THEN RETURN; END IF;
    FOR target IN SELECT tenant_id FROM public.sync_state WHERE tenant_id IS NOT NULL ORDER BY tenant_id COLLATE "C" LOOP
        allowed := wms.shared_entity_visible(kind, item_id, target.tenant_id);
        PERFORM wms.write_entity_change(kind, target.tenant_id, item_id,
            CASE WHEN allowed THEN 'upsert' ELSE 'remove' END,
            CASE WHEN allowed THEN latest.data ELSE NULL END);
    END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION wms.capture_entity_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE old_row jsonb; new_row jsonb; payload jsonb; projection record; nested jsonb;
BEGIN
    IF TG_OP <> 'INSERT' THEN old_row := to_jsonb(OLD); END IF;
    IF TG_OP <> 'DELETE' THEN new_row := to_jsonb(NEW); END IF;
    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND (old_row->>'tenant_id' IS DISTINCT FROM new_row->>'tenant_id' OR old_row->>'id' IS DISTINCT FROM new_row->>'id')) THEN
        PERFORM wms.append_entity_change(TG_ARGV[0], old_row->>'tenant_id', old_row->>'id', 'remove');
    END IF;
    IF TG_OP <> 'DELETE' THEN
        SELECT jsonb_object_agg(field, new_row->field) INTO payload FROM jsonb_array_elements_text(TG_ARGV[1]::jsonb) AS fields(field);
        payload := coalesce(payload, '{}'::jsonb) || jsonb_build_object('id', new_row->>'id');
        IF TG_NARGS > 2 THEN
            FOR projection IN SELECT key, value FROM jsonb_each(TG_ARGV[2]::jsonb) LOOP
                SELECT jsonb_object_agg(field, new_row->projection.key->field) INTO nested FROM jsonb_array_elements_text(projection.value) AS fields(field);
                payload := payload || jsonb_build_object(projection.key, coalesce(nested, '{}'::jsonb));
            END LOOP;
        END IF;
        PERFORM wms.append_entity_change(TG_ARGV[0], new_row->>'tenant_id', new_row->>'id', 'upsert', payload);
    END IF;
    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION wms.capture_entity_truncate() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE item record;
BEGIN
    FOR item IN EXECUTE format('SELECT id::text AS id, tenant_id::text AS tenant_id FROM %I.%I ORDER BY tenant_id COLLATE "C" NULLS FIRST, id::text COLLATE "C"', TG_TABLE_SCHEMA, TG_TABLE_NAME) LOOP
        PERFORM wms.append_entity_change(TG_ARGV[0], item.tenant_id, item.id, 'remove');
    END LOOP;
    RETURN NULL;
END;
$$;

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

CREATE OR REPLACE FUNCTION wms.user_roles_payload(p_user_id bigint, p_viewer text)
RETURNS jsonb LANGUAGE sql STABLE SECURITY DEFINER SET search_path = pg_catalog AS $$
    SELECT coalesce(jsonb_agg(jsonb_build_object('id', r.id::text, 'name', r.name, 'slug', r.slug, 'status', r.status) ORDER BY r.name COLLATE "C", r.id), '[]'::jsonb)
    FROM main.role_user ru JOIN main.roles r ON r.id = ru.role_id
    WHERE ru.user_id = p_user_id AND ru.status = 1 AND ru.deleted_at IS NULL AND r.status = 1 AND r.deleted_at IS NULL
      AND wms.entity_visible('App\Models\Role', r.id::text, r.tenant_id, p_viewer);
$$;

CREATE OR REPLACE FUNCTION wms.user_payload_with_roles(p_user_id bigint)
RETURNS jsonb LANGUAGE sql STABLE SECURITY DEFINER SET search_path = pg_catalog AS $$
    SELECT (to_jsonb(u) - 'password' - 'remember_token') || jsonb_build_object('roles', wms.user_roles_payload(u.id, u.tenant_id))
    FROM public.users u WHERE u.id = p_user_id;
$$;

CREATE OR REPLACE FUNCTION wms.capture_user_with_roles_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND (OLD.tenant_id IS DISTINCT FROM NEW.tenant_id OR OLD.id IS DISTINCT FROM NEW.id)) THEN
        PERFORM wms.append_entity_change('App\Models\User', OLD.tenant_id, OLD.id::text, 'remove');
    END IF;
    IF TG_OP <> 'DELETE' THEN
        PERFORM wms.append_entity_change('App\Models\User', NEW.tenant_id, NEW.id::text, 'upsert', wms.user_payload_with_roles(NEW.id));
    END IF;
    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION wms.capture_role_user_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE changed_user bigint; tenant text;
BEGIN
    changed_user := coalesce(NEW.user_id, OLD.user_id);
    SELECT u.tenant_id INTO tenant FROM public.users u WHERE u.id = changed_user;
    IF changed_user IS NOT NULL AND tenant IS NOT NULL THEN
        PERFORM wms.append_entity_change('App\Models\User', tenant, changed_user::text, 'upsert', wms.user_payload_with_roles(changed_user));
    END IF;
    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION wms.capture_tenant_entity_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF TG_OP <> 'INSERT' THEN PERFORM wms.refresh_entity_visibility(OLD.entity_type, OLD.entity_id::text); END IF;
    IF TG_OP = 'INSERT' OR (TG_OP = 'UPDATE' AND (OLD.entity_type IS DISTINCT FROM NEW.entity_type OR OLD.entity_id IS DISTINCT FROM NEW.entity_id)) THEN
        PERFORM wms.refresh_entity_visibility(NEW.entity_type, NEW.entity_id::text);
    END IF;
    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION wms.capture_tenant_entity_truncate() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE item record;
BEGIN
    FOR item IN SELECT DISTINCT ON (entity, entity_id) entity, entity_id FROM public.entity_changes WHERE tenant_id IS NULL ORDER BY entity, entity_id LOOP
        PERFORM wms.refresh_entity_visibility(item.entity, item.entity_id);
    END LOOP;
    RETURN NULL;
END;
$$;

CREATE TRIGGER wms_tenant_entity_change AFTER INSERT OR UPDATE OR DELETE ON main.tenant_entity FOR EACH ROW EXECUTE FUNCTION wms.capture_tenant_entity_change();
CREATE TRIGGER wms_tenant_entity_truncate AFTER TRUNCATE ON main.tenant_entity FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_tenant_entity_truncate();

REVOKE ALL ON FUNCTION wms.write_entity_change(text, text, text, text, jsonb) FROM PUBLIC;
REVOKE ALL ON FUNCTION wms.append_entity_change(text, text, text, text, jsonb) FROM PUBLIC;
REVOKE ALL ON FUNCTION wms.initialize_tenant_shares(text) FROM PUBLIC;
REVOKE ALL ON FUNCTION wms.refresh_entity_visibility(text, text) FROM PUBLIC;
