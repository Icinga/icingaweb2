/* Icinga Web 2 | (c) 2014 Icinga GmbH | GPLv2+ */

CREATE OR REPLACE FUNCTION unix_timestamp(timestamp with time zone) RETURNS bigint AS '
        SELECT EXTRACT(EPOCH FROM $1)::bigint AS result
' LANGUAGE sql;

CREATE EXTENSION IF NOT EXISTS citext;

CREATE DOMAIN binary20 AS bytea CONSTRAINT exactly_20_bytes_long CHECK (VALUE IS NULL OR octet_length(VALUE) = 20);

CREATE DOMAIN tinyuint AS smallint CONSTRAINT between_0_and_255 CHECK (VALUE IS NULL OR VALUE BETWEEN 0 AND 255);

CREATE TYPE boolenum AS ENUM ('n', 'y');
CREATE TYPE dashboard_type AS ENUM ('public', 'private', 'shared');

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

CREATE TABLE "icingaweb_schema" (
  "id"          serial,
  "version"     smallint NOT NULL,
  "timestamp"   int NOT NULL,

  CONSTRAINT pk_icingaweb_schema PRIMARY KEY ("id")
);

INSERT INTO icingaweb_schema (version, timestamp)
  VALUES (6, extract(epoch from now()));

-- Icinga Web 2 enhanced Dashboards

CREATE TABLE icingaweb_dashboard_owner (
  id serial,
  username citext NOT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX idx_dashboard_user_username ON icingaweb_dashboard_owner (username);

CREATE TABLE icingaweb_dashboard_home (
  id serial,
  user_id int NOT NULL REFERENCES icingaweb_dashboard_owner (id),
  name character varying(64) NOT NULL,
  label character varying(64) NOT NULL,
  priority tinyuint NOT NULL,
  type dashboard_type DEFAULT 'private',
  disabled boolenum DEFAULT 'n',

  PRIMARY KEY (id)
);

CREATE INDEX idx_dashboard_home ON icingaweb_dashboard_home (user_id);

CREATE TABLE icingaweb_dashboard (
  id binary20 NOT NULL,
  home_id int NOT NULL REFERENCES icingaweb_dashboard_home (id),
  name character varying(64) NOT NULL,
  label character varying(64) NOT NULL,
  priority tinyuint NOT NULL,

  PRIMARY KEY (id)
);

ALTER TABLE icingaweb_dashboard ALTER COLUMN id SET STORAGE PLAIN;

CREATE TABLE icingaweb_dashlet (
  id binary20 NOT NULL,
  dashboard_id binary20 NOT NULL REFERENCES icingaweb_dashboard (id),
  name character varying(64) NOT NULL,
  label character varying(254) NOT NULL,
  url character varying(2048) NOT NULL,
  priority tinyuint NOT NULL,
  disabled boolenum DEFAULT 'n',
  description text DEFAULT NULL,

  PRIMARY KEY (id)
);

ALTER TABLE icingaweb_dashlet ALTER COLUMN id SET STORAGE PLAIN;
ALTER TABLE icingaweb_dashlet ALTER COLUMN dashboard_id SET STORAGE PLAIN;

CREATE TABLE icingaweb_module_dashlet (
  id binary20 NOT NULL,
  name citext NOT NULL,
  label character varying(64) NOT NULL,
  module citext NOT NULL,
  pane citext DEFAULT NULL,
  url character varying(2048) NOT NULL,
  description text DEFAULT NULL,
  priority tinyuint DEFAULT 0,

  PRIMARY KEY (id)
);

ALTER TABLE icingaweb_module_dashlet ALTER COLUMN id SET STORAGE PLAIN;
CREATE INDEX idx_module_dashlet_name ON icingaweb_module_dashlet (name);
CREATE INDEX idx_module_dashlet_pane ON icingaweb_module_dashlet (pane);
CREATE INDEX idx_module_dashlet_module ON icingaweb_module_dashlet (module);

CREATE TABLE icingaweb_system_dashlet (
  dashlet_id binary20 NOT NULL REFERENCES icingaweb_dashlet (id),
  module_dashlet_id binary20 DEFAULT NULL REFERENCES icingaweb_module_dashlet (id),

  PRIMARY KEY (dashlet_id)
);

ALTER TABLE icingaweb_system_dashlet ALTER COLUMN dashlet_id SET STORAGE PLAIN;
ALTER TABLE icingaweb_system_dashlet ALTER COLUMN module_dashlet_id SET STORAGE PLAIN;

