create table "preference"(
  "username"    VARCHAR(255) NOT NULL,
  "key"         VARCHAR(100) NOT NULL,
  "value"       VARCHAR(255) NOT NULL
);

ALTER TABLE ONLY "preference"
  ADD CONSTRAINT preference_pkey PRIMARY KEY ("username", "key");

CREATE UNIQUE INDEX username_and_key_lower_unique_idx ON "preference" USING btree (lower((username)::text), lower((key)::text));