CREATE TABLE "icingaweb_2fa" (
  "username" varchar(254) NOT NULL,
  "secret"   varchar(255) NOT NULL,
  "ctime"    bigint,
  CONSTRAINT pk_icingaweb_2fa PRIMARY KEY ("username")
);
