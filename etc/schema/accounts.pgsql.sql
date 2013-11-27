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
    '57cfd5746224be4f60c25d4e8514bec35ad2d01810723a138756b285898e71b2',
    '43f8e0588eb39f1a41383b48def0b1fdc45e79b8f67194cccee4453eb3f4ea13',
    true
  );
