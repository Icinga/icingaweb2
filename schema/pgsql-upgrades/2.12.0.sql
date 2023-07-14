CREATE EXTENSION IF NOT EXISTS citext;

CREATE DOMAIN binary20 AS bytea CONSTRAINT exactly_20_bytes_long CHECK (VALUE IS NULL OR octet_length(VALUE) = 20);
CREATE DOMAIN tinyuint AS smallint CONSTRAINT between_0_and_255 CHECK (VALUE IS NULL OR VALUE BETWEEN 0 AND 255);

CREATE TYPE boolenum AS ENUM ('n', 'y');
CREATE TYPE dashboard_type AS ENUM ('public', 'private', 'shared');

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
