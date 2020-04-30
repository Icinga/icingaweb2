CREATE TABLE "icingaweb_rememberme" (
  "id"          serial,
  "username"    character varying(254) NOT NULL,
  "public_key"  text NOT NULL,
  "private_key" text NOT NULL,
  "expires_at"  timestamp NULL DEFAULT NULL,
  "ctime"       timestamp NULL DEFAULT NULL,
  "mtime"       timestamp NULL DEFAULT NULL
);

ALTER TABLE ONLY "icingaweb_rememberme"
  ADD CONSTRAINT pk_icingaweb_rememberme
  PRIMARY KEY (
    "id"
);

CREATE UNIQUE INDEX idx_icingaweb_rememberme
  ON "icingaweb_rememberme"
  USING btree (
    lower((username)::text)
);
