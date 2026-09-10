CREATE SCHEMA main;
CREATE TABLE public.users (
 id bigserial PRIMARY KEY, name varchar(255), last_name varchar(255), middle_name varchar(255), nick varchar(255),
 email varchar(255), phone varchar(255), password varchar(255), remember_token varchar(100),
 status integer DEFAULT 1, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp
);
CREATE TABLE main.roles (id bigserial PRIMARY KEY, slug varchar(255), name varchar(255), status integer DEFAULT 1, deleted_at timestamp, tenant_id varchar(255));
CREATE TABLE main.role_user (id bigserial PRIMARY KEY, role_id bigint, user_id bigint, tenant_id varchar(255), status integer DEFAULT 1, deleted_at timestamp);
CREATE TABLE public.personal_access_tokens (
 id bigserial PRIMARY KEY, tokenable_type varchar(255) NOT NULL, tokenable_id bigint NOT NULL,
 name varchar(255) NOT NULL, token varchar(64) NOT NULL UNIQUE, abilities text,
 last_used_at timestamp, expires_at timestamp, created_at timestamp, updated_at timestamp
);
CREATE INDEX public_tokens_owner ON public.personal_access_tokens (tokenable_type, tokenable_id);
ALTER TABLE main.roles ADD COLUMN description text, ADD COLUMN system boolean NOT NULL DEFAULT false,
 ADD COLUMN tags json, ADD COLUMN company_id integer, ADD COLUMN created_at timestamp, ADD COLUMN updated_at timestamp;
CREATE TABLE main.permissions (id bigserial PRIMARY KEY, name varchar(255) NOT NULL, slug varchar(255) NOT NULL,
 resource varchar(255) NOT NULL, system boolean NOT NULL DEFAULT false, status integer, module_id integer,
 feature_id integer, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE main.permission_role (id bigserial PRIMARY KEY, role_id integer NOT NULL, permission_id integer NOT NULL,
 status integer DEFAULT 1, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);

CREATE TABLE main.companies (
 id bigserial PRIMARY KEY, name varchar(255) NOT NULL, shortname varchar(255) NOT NULL,
 fullname varchar(255), inn varchar(255), kpp varchar(255), ogrn varchar(255), phone varchar(255), email varchar(255), site varchar(255),
 src json, director_fio varchar(255), director_position varchar(255), bank varchar(255), bik varchar(255), korr_schet varchar(255), rasch_schet varchar(255),
 status integer, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp
);
CREATE TABLE main.company_contacts (
 id bigserial PRIMARY KEY, name varchar(255) NOT NULL, shortname varchar(255) NOT NULL, company_id integer NOT NULL,
 val varchar(255), file varchar(255), status integer, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp
);

CREATE SCHEMA clients;
CREATE TABLE clients.clients (id bigserial PRIMARY KEY, name varchar(255), shortname varchar(255), status integer, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);

CREATE TABLE main.modules (id bigserial PRIMARY KEY, name varchar(255), shortname varchar(255), descr text, fn varchar(255), domain varchar(255), status integer, created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE main.features (id bigserial PRIMARY KEY, name varchar(255), shortname varchar(255), is_resource integer, module_id integer, status integer, created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE main.icons (id bigserial PRIMARY KEY, name varchar(255), path varchar(255), category varchar(255), size integer, ext varchar(255), user_id integer, company_id integer, status integer, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE main.files (id bigserial PRIMARY KEY, name varchar(255), path varchar(255), category varchar(255), size integer, ext varchar(255), user_id integer, company_id integer, is_s3 integer, status integer NOT NULL, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE clients.companies (
 id bigserial PRIMARY KEY, name varchar(255) NOT NULL, shortname varchar(255) NOT NULL,
 fullname varchar(255), inn varchar(255), kpp varchar(255), ogrn varchar(255), okpo varchar(255),
 phone varchar(255), email varchar(255), site varchar(255), director_fio varchar(255), director_position varchar(255),
 bank varchar(255), bik varchar(255), korr_schet varchar(255), rasch_schet varchar(255),
 company_src json, bank_src json, src json, client_id integer, status integer, tenant_id varchar(255),
 created_at timestamp, updated_at timestamp, deleted_at timestamp
);
CREATE TABLE clients.individuals (
 id bigserial PRIMARY KEY, name varchar(255), shortname varchar(255), firstname varchar(255), middlename varchar(255), lastname varchar(255),
 phone varchar(255), email varchar(255), birthday date, passport_seria varchar(255), passport_number varchar(255), passport_date date,
 passport_kem varchar(255), passport_code varchar(255), address_reg varchar(255), vodud_date date, vodud_nomer varchar(255),
 user_id integer, manager_id integer, client_id integer, status integer, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp
);

CREATE SCHEMA goods;
CREATE TABLE goods.goods (
 id bigserial PRIMARY KEY, parent_id integer, parent_code varchar(255), name varchar(255), shortname varchar(255), code varchar(255),
 type_good integer, type_unit integer, barcodes json, is_from_external integer DEFAULT 2, is_category integer DEFAULT 2,
 status integer DEFAULT 1, created_at timestamp, updated_at timestamp, deleted_at timestamp, level integer NOT NULL, goodcard_id integer NOT NULL, tenant_id varchar(255)
);
CREATE TABLE goods.good_cards (id bigserial PRIMARY KEY, name varchar(255), unit_id integer, status integer DEFAULT 1, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE goods.type_goods (id bigserial PRIMARY KEY, name varchar(255), shortname varchar(255), status integer DEFAULT 1, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);
CREATE TABLE goods.unit_goods (id bigserial PRIMARY KEY, name varchar(255), shortname varchar(255), status integer DEFAULT 1, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp);
