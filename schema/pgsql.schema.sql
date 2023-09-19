/* Icinga Web 2 | (c) 2014 Icinga GmbH | GPLv2+ */

CREATE OR REPLACE FUNCTION unix_timestamp(timestamp with time zone) RETURNS bigint AS '
        SELECT EXTRACT(EPOCH FROM $1)::bigint AS result
' LANGUAGE sql;

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

CREATE TYPE boolenum AS ENUM ('n', 'y');

CREATE TABLE "icingaweb_schema" (
  "id"          serial,
  "version"     varchar(64) NOT NULL,
  "timestamp"   bigint NOT NULL,
  "success"     boolenum DEFAULT NULL,
  "reason"      text DEFAULT NULL,

  CONSTRAINT pk_icingaweb_schema PRIMARY KEY ("id"),
  CONSTRAINT idx_icingaweb_schema_version UNIQUE (version)
);

INSERT INTO icingaweb_schema (version, timestamp, success)
  VALUES ('2.12.0', extract(epoch from now()) * 1000, 'y');
