create table "preferences"(
  "username"    VARCHAR(255) NOT NULL,
  "preference"  VARCHAR(100) NOT NULL,
  "value"       VARCHAR(255) NOT NULL,

  PRIMARY KEY(username, preference)
);