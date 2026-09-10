CREATE SCHEMA main;
CREATE TABLE public.users (
 id bigserial PRIMARY KEY, name varchar(255), last_name varchar(255), middle_name varchar(255), nick varchar(255),
 email varchar(255), phone varchar(255), password varchar(255), remember_token varchar(100),
 status integer DEFAULT 1, tenant_id varchar(255), created_at timestamp, updated_at timestamp, deleted_at timestamp
);
CREATE TABLE main.roles (id bigserial PRIMARY KEY, slug varchar(255), name varchar(255), status integer DEFAULT 1, deleted_at timestamp, tenant_id varchar(255));
CREATE TABLE main.role_user (id bigserial PRIMARY KEY, role_id bigint, user_id bigint, tenant_id varchar(255), status integer DEFAULT 1, deleted_at timestamp);
