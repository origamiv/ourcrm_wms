CREATE TRIGGER wms_users_change AFTER INSERT OR UPDATE OR DELETE ON public.users
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\User', '["name","last_name","middle_name","nick","email","phone","status","tenant_id","created_at","updated_at","deleted_at"]');
CREATE TRIGGER wms_users_truncate BEFORE TRUNCATE ON public.users
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\User');
