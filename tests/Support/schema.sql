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
