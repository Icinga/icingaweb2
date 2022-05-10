/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

CREATE OR REPLACE FUNCTION unix_timestamp(timestamp with time zone) RETURNS bigint AS '
        SELECT EXTRACT(EPOCH FROM $1)::bigint AS result
' LANGUAGE sql;

CREATE EXTENSION IF NOT EXISTS citext;
CREATE DOMAIN bytea20 AS bytea CONSTRAINT exactly_20_bytes_long CHECK ( VALUE IS NULL OR octet_length(VALUE) = 20 );

CREATE TABLE "icingaweb_group" (
  "id"     serial,
  "name"   character varying(64) NOT NULL,
  "parent" int NULL DEFAULT NULL,
  "ctime"  timestamp NULL DEFAULT NULL,
  "mtime"  timestamp NULL DEFAULT NULL
);

ALTER TABLE ONLY "icingaweb_group"
  ADD CONSTRAINT pk_icingaweb_group
  PRIMARY KEY (
    "id"
);

CREATE UNIQUE INDEX idx_icingaweb_group
  ON "icingaweb_group"
  USING btree (
    lower((name)::text)
);

ALTER TABLE ONLY "icingaweb_group"
  ADD CONSTRAINT fk_icingaweb_group_parent_id
  FOREIGN KEY (
    "parent"
  )
  REFERENCES "icingaweb_group" (
    "id"
);

CREATE TABLE "icingaweb_group_membership" (
  "group_id"   int NOT NULL,
  "username"   character varying(254) NOT NULL,
  "ctime"      timestamp NULL DEFAULT NULL,
  "mtime"      timestamp NULL DEFAULT NULL
);

ALTER TABLE ONLY "icingaweb_group_membership"
  ADD CONSTRAINT pk_icingaweb_group_membership
  FOREIGN KEY (
    "group_id"
  )
  REFERENCES "icingaweb_group" (
    "id"
);

CREATE UNIQUE INDEX idx_icingaweb_group_membership
  ON "icingaweb_group_membership"
  USING btree (
    group_id,
    lower((username)::text)
);

CREATE TABLE "icingaweb_user" (
  "name"          character varying(254) NOT NULL,
  "active"        smallint NOT NULL,
  "password_hash" bytea NOT NULL,
  "ctime"         timestamp NULL DEFAULT NULL,
  "mtime"         timestamp NULL DEFAULT NULL
);

ALTER TABLE ONLY "icingaweb_user"
  ADD CONSTRAINT pk_icingaweb_user
  PRIMARY KEY (
    "name"
);

CREATE UNIQUE INDEX idx_icingaweb_user
  ON "icingaweb_user"
  USING btree (
    lower((name)::text)
);

CREATE TABLE "icingaweb_user_preference" (
  "username" character varying(254) NOT NULL,
  "name"     character varying(64) NOT NULL,
  "section"  character varying(64) NOT NULL,
  "value"    character varying(255) NOT NULL,
  "ctime"    timestamp NULL DEFAULT NULL,
  "mtime"    timestamp NULL DEFAULT NULL
);

ALTER TABLE ONLY "icingaweb_user_preference"
  ADD CONSTRAINT pk_icingaweb_user_preference
  PRIMARY KEY (
    "username",
    "section",
    "name"
);

CREATE UNIQUE INDEX idx_icingaweb_user_preference
  ON "icingaweb_user_preference"
  USING btree (
    lower((username)::text),
    lower((section)::text),
    lower((name)::text)
);

CREATE TABLE "icingaweb_rememberme" (
  "id"                  serial,
  "username"            character varying(254) NOT NULL,
  "passphrase"          character varying(256) NOT NULL,
  "random_iv"           character varying(32) NOT NULL,
  "http_user_agent"     text NOT NULL,
  "expires_at"          timestamp NULL DEFAULT NULL,
  "ctime"               timestamp NULL DEFAULT NULL,
  "mtime"               timestamp NULL DEFAULT NULL
);

ALTER TABLE ONLY "icingaweb_rememberme"
  ADD CONSTRAINT pk_icingaweb_rememberme
  PRIMARY KEY (
    "id"
);

CREATE TABLE "icingaweb_config_scope" (
  "id"     serial,
  "module" character varying(254) NOT NULL DEFAULT 'default',
  "type"   character varying(64) NOT NULL,
  "name"   citext NOT NULL,
  "hash"   bytea20 NOT NULL
);

COMMENT ON COLUMN icingaweb_config_scope.hash IS 'sha1(all option tuples)';

ALTER TABLE ONLY "icingaweb_config_scope"
  ADD CONSTRAINT pk_icingaweb_config_scope
  PRIMARY KEY (
    "id"
);

CREATE UNIQUE INDEX idx_module_type_name
  ON "icingaweb_config_scope"
  USING btree (
    lower((module)::text),
    lower((type)::text),
    lower((name)::text)
);

CREATE TABLE "icingaweb_config_option" (
  "scope_id" int NOT NULL,
  "name"     character varying(254) NOT NULL,
  "value"    text DEFAULT NULL
);

CREATE UNIQUE INDEX idx_scope_id_name
  ON "icingaweb_config_option"
  USING btree (
    scope_id,
    lower((name)::text)
);

ALTER TABLE ONLY "icingaweb_config_option"
  ADD CONSTRAINT fk_scope_id_config_scope
  FOREIGN KEY (
    "scope_id"
  )
  REFERENCES "icingaweb_config_scope" (
    "id"
) ON DELETE CASCADE;
