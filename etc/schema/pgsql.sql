create table "account" (
  "username"   character varying(255)  NOT NULL,
  "salt"       character varying(255),
  "password"   character varying(255)  NOT NULL,
  "active"     boolean
);

ALTER TABLE ONLY "account"
    ADD CONSTRAINT account_pkey PRIMARY KEY ("username");

CREATE UNIQUE INDEX username_lower_unique_idx ON "account" USING btree (lower((username)::text));

create table "preference"(
  "username"    VARCHAR(255) NOT NULL,
  "key"         VARCHAR(100) NOT NULL,
  "value"       VARCHAR(255) NOT NULL
);

ALTER TABLE ONLY "preference"
  ADD CONSTRAINT preference_pkey PRIMARY KEY ("username", "key");

CREATE UNIQUE INDEX username_and_key_lower_unique_idx ON "preference" USING btree (lower((username)::text), lower((key)::text));