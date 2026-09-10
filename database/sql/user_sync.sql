CREATE SCHEMA IF NOT EXISTS wms;
-- Блокировка защищает начальный снимок от записей во время установки триггера.
LOCK TABLE public.users IN SHARE ROW EXCLUSIVE MODE;
CREATE TABLE wms.sync_state (id smallint PRIMARY KEY CHECK (id = 1), revision bigint NOT NULL,
    generation varchar(32) NOT NULL DEFAULT md5(random()::text || clock_timestamp()::text));
INSERT INTO wms.sync_state (id, revision) VALUES (1, 0);
CREATE TABLE wms.user_changes (
    revision bigint PRIMARY KEY,
    tenant_id varchar(255),
    user_id bigint NOT NULL,
    operation varchar(10) NOT NULL,
    data jsonb,
    created_at timestamptz NOT NULL DEFAULT clock_timestamp()
);
CREATE INDEX user_changes_tenant_revision ON wms.user_changes (tenant_id, revision);
CREATE INDEX user_changes_user_revision ON wms.user_changes (user_id, revision DESC);
CREATE FUNCTION wms.user_payload(u public.users) RETURNS jsonb LANGUAGE sql IMMUTABLE AS $$
SELECT jsonb_build_object(
    'id', u.id::text, 'name', u.name, 'last_name', u.last_name, 'middle_name', u.middle_name,
    'nick', u.nick, 'email', u.email, 'phone', u.phone, 'status', u.status,
    'tenant_id', u.tenant_id, 'created_at', u.created_at, 'updated_at', u.updated_at, 'deleted_at', u.deleted_at
);
$$;
INSERT INTO wms.user_changes (revision, tenant_id, user_id, operation, data)
SELECT row_number() OVER (ORDER BY id), tenant_id, id, 'upsert', wms.user_payload(u) FROM public.users u;
UPDATE wms.sync_state SET revision = (SELECT coalesce(max(revision), 0) FROM wms.user_changes);
CREATE FUNCTION wms.capture_user_change() RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE rev bigint;
BEGIN
    -- Счётчик транзакционный: следующая транзакция получает ревизию только после commit/rollback предыдущей.
    IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND (OLD.tenant_id IS DISTINCT FROM NEW.tenant_id OR OLD.id IS DISTINCT FROM NEW.id)) THEN
        UPDATE wms.sync_state SET revision = revision + 1 WHERE id = 1 RETURNING revision INTO rev;
        INSERT INTO wms.user_changes (revision, tenant_id, user_id, operation) VALUES (rev, OLD.tenant_id, OLD.id, 'remove');
    END IF;
    IF TG_OP <> 'DELETE' THEN
        UPDATE wms.sync_state SET revision = revision + 1 WHERE id = 1 RETURNING revision INTO rev;
        INSERT INTO wms.user_changes (revision, tenant_id, user_id, operation, data)
        VALUES (rev, NEW.tenant_id, NEW.id, 'upsert', wms.user_payload(NEW));
    END IF;
    RETURN NULL;
END;
$$;
CREATE TRIGGER wms_users_change AFTER INSERT OR UPDATE OR DELETE ON public.users
FOR EACH ROW EXECUTE FUNCTION wms.capture_user_change();

CREATE FUNCTION wms.capture_users_truncate() RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
DECLARE item record; rev bigint;
BEGIN
    FOR item IN SELECT id, tenant_id FROM public.users ORDER BY id LOOP
        UPDATE wms.sync_state SET revision = revision + 1 WHERE id = 1 RETURNING revision INTO rev;
        INSERT INTO wms.user_changes (revision, tenant_id, user_id, operation) VALUES (rev, item.tenant_id, item.id, 'remove');
    END LOOP;
    RETURN NULL;
END;
$$;
CREATE TRIGGER wms_users_truncate BEFORE TRUNCATE ON public.users
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_users_truncate();
