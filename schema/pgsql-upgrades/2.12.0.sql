CREATE TYPE boolenum AS ENUM ('n', 'y');

ALTER TABLE icingaweb_schema
  ALTER COLUMN timestamp TYPE bigint,
  ALTER COLUMN version TYPE varchar(64),
  ADD COLUMN success boolenum DEFAULT NULL,
  ADD COLUMN reason text DEFAULT NULL,
  ADD CONSTRAINT idx_icingaweb_schema_version UNIQUE (version);

UPDATE icingaweb_schema SET timestamp = timestamp * 1000, success = 'y';

INSERT INTO icingaweb_schema (version, timestamp, success, reason)
  VALUES('2.12.0', EXTRACT(EPOCH FROM now()) * 1000, 'y', NULL);
