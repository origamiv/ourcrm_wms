LOCK TABLE main.companies, main.company_contacts IN SHARE ROW EXCLUSIVE MODE;
CREATE TRIGGER wms_companies_change AFTER INSERT OR UPDATE OR DELETE ON main.companies
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\Company', '["name", "shortname", "fullname", "inn", "kpp", "ogrn", "phone", "email", "site", "director_fio", "director_position", "bank", "bik", "korr_schet", "rasch_schet", "src", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]', '{"src": ["telegram", "opf", "accountant_position", "accountant_fio", "legal_address", "is_own", "is_client", "is_partner"]}');
CREATE TRIGGER wms_companies_truncate BEFORE TRUNCATE ON main.companies
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\Company');
CREATE TRIGGER wms_company_contacts_change AFTER INSERT OR UPDATE OR DELETE ON main.company_contacts
FOR EACH ROW EXECUTE FUNCTION wms.capture_entity_change('App\Models\CompanyContact', '["name", "shortname", "company_id", "val", "status", "tenant_id", "created_at", "updated_at", "deleted_at"]');
CREATE TRIGGER wms_company_contacts_truncate BEFORE TRUNCATE ON main.company_contacts
FOR EACH STATEMENT EXECUTE FUNCTION wms.capture_entity_truncate('App\Models\CompanyContact');
DO $$
DECLARE item record;
BEGIN
    FOR item IN SELECT * FROM (SELECT 'App\Models\Company' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'fullname', fullname, 'inn', inn, 'kpp', kpp, 'ogrn', ogrn, 'phone', phone, 'email', email, 'site', site, 'director_fio', director_fio, 'director_position', director_position, 'bank', bank, 'bik', bik, 'korr_schet', korr_schet, 'rasch_schet', rasch_schet, 'src', jsonb_build_object('telegram', src::jsonb->'telegram', 'opf', src::jsonb->'opf', 'accountant_position', src::jsonb->'accountant_position', 'accountant_fio', src::jsonb->'accountant_fio', 'legal_address', src::jsonb->'legal_address', 'is_own', src::jsonb->'is_own', 'is_client', src::jsonb->'is_client', 'is_partner', src::jsonb->'is_partner'), 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM main.companies UNION ALL SELECT 'App\Models\CompanyContact' AS entity, id::text AS id, tenant_id, jsonb_build_object('id', id::text, 'name', name, 'shortname', shortname, 'company_id', company_id, 'val', val, 'status', status, 'tenant_id', tenant_id, 'created_at', created_at, 'updated_at', updated_at, 'deleted_at', deleted_at) AS data FROM main.company_contacts) entries ORDER BY tenant_id COLLATE "C" NULLS FIRST, entity, id
    LOOP
        PERFORM wms.append_entity_change(item.entity, item.tenant_id, item.id, 'upsert', item.data);
    END LOOP;
END;
$$;
