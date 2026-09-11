CREATE OR REPLACE FUNCTION wms.user_roles_payload(p_user_id bigint, p_viewer text)
RETURNS jsonb LANGUAGE sql STABLE SECURITY DEFINER SET search_path = pg_catalog AS $$
    SELECT coalesce(jsonb_agg(jsonb_build_object(
        'id', r.id::text, 'name', r.name, 'slug', r.slug, 'status', r.status
    ) ORDER BY r.name COLLATE "C", r.id), '[]'::jsonb)
    FROM main.role_user ru
    JOIN main.roles r ON r.id = ru.role_id
    WHERE ru.user_id = p_user_id
      AND ru.status = 1
      AND ru.deleted_at IS NULL
      AND r.status = 1
      AND r.deleted_at IS NULL
      AND wms.entity_visible('App\Models\Role', r.id::text, r.tenant_id, p_viewer);
$$;

CREATE OR REPLACE FUNCTION wms.user_payload_with_roles(p_user_id bigint)
RETURNS jsonb LANGUAGE sql STABLE SECURITY DEFINER SET search_path = pg_catalog AS $$
    SELECT (to_jsonb(u) - 'password' - 'remember_token')
        || jsonb_build_object('roles', wms.user_roles_payload(u.id, u.tenant_id))
    FROM public.users u
    WHERE u.id = p_user_id;
$$;

CREATE OR REPLACE FUNCTION wms.capture_role_user_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE changed_user bigint;
DECLARE tenant text;
DECLARE payload jsonb;
BEGIN
    changed_user := coalesce(NEW.user_id, OLD.user_id);
    SELECT u.tenant_id INTO tenant FROM public.users u WHERE u.id = changed_user;
    IF changed_user IS NOT NULL AND tenant IS NOT NULL THEN
        payload := wms.user_payload_with_roles(changed_user);
        PERFORM wms.append_entity_change('App\Models\User', tenant, changed_user::text, 'upsert', payload);
    END IF;
    RETURN NULL;
END;
$$;

CREATE OR REPLACE FUNCTION wms.capture_user_with_roles_change() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND
        (OLD.tenant_id IS DISTINCT FROM NEW.tenant_id OR OLD.id IS DISTINCT FROM NEW.id)) THEN
        PERFORM wms.append_entity_change('App\Models\User', OLD.tenant_id, OLD.id::text, 'remove');
    END IF;
    IF TG_OP <> 'DELETE' THEN
        PERFORM wms.append_entity_change(
            'App\Models\User', NEW.tenant_id, NEW.id::text, 'upsert',
            wms.user_payload_with_roles(NEW.id)
        );
    END IF;
    RETURN NULL;
END;
$$;

DROP TRIGGER IF EXISTS wms_users_change ON public.users;
CREATE TRIGGER wms_users_change AFTER INSERT OR UPDATE OR DELETE ON public.users
FOR EACH ROW EXECUTE FUNCTION wms.capture_user_with_roles_change();
DROP TRIGGER IF EXISTS wms_role_user_change ON main.role_user;
CREATE TRIGGER wms_role_user_change AFTER INSERT OR UPDATE OR DELETE ON main.role_user
FOR EACH ROW EXECUTE FUNCTION wms.capture_role_user_change();

DO $$
DECLARE item record;
BEGIN
    FOR item IN SELECT id, tenant_id FROM public.users WHERE tenant_id IS NOT NULL ORDER BY id LOOP
        PERFORM wms.append_entity_change(
            'App\Models\User', item.tenant_id, item.id::text, 'upsert',
            wms.user_payload_with_roles(item.id)
        );
    END LOOP;
END;
$$;
