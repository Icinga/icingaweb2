ALTER TABLE icingaweb_schema
  MODIFY COLUMN timestamp bigint unsigned NOT NULL,
  MODIFY COLUMN version varchar(64) NOT NULL,
  ADD COLUMN success enum('n', 'y') DEFAULT NULL,
  ADD COLUMN reason text DEFAULT NULL,
  ADD CONSTRAINT idx_icingaweb_schema_version UNIQUE (version);

UPDATE icingaweb_schema SET timestamp = timestamp * 1000, success = 'y';

INSERT INTO icingaweb_schema (version, timestamp, success, reason)
  VALUES('2.12.0', UNIX_TIMESTAMP() * 1000, 'y', NULL);
