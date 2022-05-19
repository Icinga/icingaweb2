CREATE EXTENSION IF NOT EXISTS citext;
CREATE DOMAIN bytea20 AS bytea CONSTRAINT exactly_20_bytes_long CHECK ( VALUE IS NULL OR octet_length(VALUE) = 20 );

CREATE TABLE "icingaweb_config_scope" (
  "id"     serial,
  "module" character varying(254) NOT NULL DEFAULT 'default',
  "name"   citext NOT NULL,
  "hash"   bytea20 NOT NULL
);

COMMENT ON COLUMN icingaweb_config_scope.hash IS 'sha1(all option tuples)';

ALTER TABLE ONLY "icingaweb_config_scope"
  ADD CONSTRAINT pk_icingaweb_config_scope
  PRIMARY KEY (
    "id"
);

CREATE UNIQUE INDEX idx_module_name
  ON "icingaweb_config_scope"
  USING btree (
    lower((module)::text),
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
