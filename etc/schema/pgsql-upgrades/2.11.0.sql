CREATE TABLE "icingaweb_schema" (
  "id"          serial,
  "version"     smallint NOT NULL,
  "timestamp"   int NOT NULL,

  CONSTRAINT pk_icingaweb_schema PRIMARY KEY ("id")
);

INSERT INTO icingaweb_schema ("version", "timestamp")
  VALUES (6, extract(epoch from now()));
