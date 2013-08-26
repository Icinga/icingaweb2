create table "account" (
  "username"   character varying(255)  NOT NULL,
  "salt"       character varying(255),
  "password"   character varying(255)  NOT NULL,
  "active"     boolean
);

ALTER TABLE ONLY "account"
    ADD CONSTRAINT account_pkey PRIMARY KEY ("username");

CREATE UNIQUE INDEX username_lower_unique_idx ON "account" USING btree (lower((username)::text));

/*
 *  user:     icingaadmin
 *  password: icinga
 */
INSERT INTO "account" (
    "username",
    "salt",
    "password",
    "active"
  )
  VALUES (
    'icingaadmin',
    'IepKgTTShC',
    '52deddb5cc7a5769484fcb0fbc5981a7c62cd9f3ddbb8ff3ddb1b89ea324ad16',
    true
  );