CREATE TABLE "rememberme" (
    "id" serial,
    "username" character varying(254) NOT NULL,
    "public_key" text NOT NULL,
    "private_key" text NOT NULL,
    "expires_in" timestamp NULL DEFAULT NULL,
    "ctime" timestamp NULL DEFAULT NULL,
    "mtime" timestamp NULL DEFAULT NULL
);

ALTER TABLE ONLY "rememberme"
  ADD CONSTRAINT pk_rememberme
  PRIMARY KEY (
    "id"
);

CREATE UNIQUE INDEX idx_rememberme
  ON "rememberme"
  USING btree (
    lower((username)::text)
);
