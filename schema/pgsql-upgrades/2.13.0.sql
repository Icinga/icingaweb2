CREATE TABLE "icingaweb_2fa" (
  "username" varchar(254) NOT NULL,
  "secret"   varchar(255) NOT NULL,
  "ctime"    bigint NOT NULL,
  CONSTRAINT pk_icingaweb_2fa PRIMARY KEY ("username")
);

CREATE UNIQUE INDEX idx_icingaweb_2fa
  ON "icingaweb_2fa"
  USING btree (
    lower((username)::text)
);
