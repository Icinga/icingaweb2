CREATE TABLE "icingaweb_totp" (
  "username" varchar(254) NOT NULL,
  "secret"   varchar(255) NOT NULL,
  "ctime"    bigint,
  CONSTRAINT pk_icingaweb_totp PRIMARY KEY ("username")
);
