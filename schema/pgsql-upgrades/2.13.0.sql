CREATE TABLE icingaweb_role (
  id           serial,
  parent_id    int DEFAULT NULL,
  name         varchar(254) NOT NULL,
  unrestricted boolenum NOT NULL DEFAULT 'n',
  ctime        bigint NOT NULL,
  mtime        bigint DEFAULT NULL,

  CONSTRAINT pk_icingaweb_role PRIMARY KEY (id),
  CONSTRAINT fk_icingaweb_role_parent_id FOREIGN KEY (parent_id)
    REFERENCES icingaweb_role (id) ON DELETE SET NULL,
  CONSTRAINT idx_icingaweb_role_name UNIQUE (name)
);

CREATE TABLE icingaweb_role_user (
  role_id   int NOT NULL,
  user_name citext NOT NULL,

  CONSTRAINT pk_icingaweb_role_user PRIMARY KEY (role_id, user_name),
  CONSTRAINT fk_icingaweb_role_user_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
);

CREATE INDEX idx_icingaweb_role_user_user_name ON icingaweb_role_user(user_name);

CREATE TABLE icingaweb_role_group (
  role_id    int NOT NULL,
  group_name citext NOT NULL,

  CONSTRAINT pk_icingaweb_role_group PRIMARY KEY (role_id, group_name),
  CONSTRAINT fk_icingaweb_role_group_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
);

CREATE INDEX idx_icingaweb_role_group_group_name ON icingaweb_role_group(group_name);

CREATE TABLE icingaweb_role_permission (
  role_id       int NOT NULL,
  permission    varchar(254) NOT NULL,
  allowed       boolenum NOT NULL DEFAULT 'n',
  denied        boolenum NOT NULL DEFAULT 'n',

  CONSTRAINT pk_icingaweb_role_permission PRIMARY KEY (role_id, permission),
  CONSTRAINT fk_icingaweb_role_permission_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
);

CREATE TABLE icingaweb_role_restriction (
  role_id       int NOT NULL,
  restriction   varchar(254) NOT NULL,
  filter        text NOT NULL,

  CONSTRAINT pk_icingaweb_role_restriction PRIMARY KEY (role_id, restriction),
  CONSTRAINT fk_icingaweb_role_restriction_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
);

INSERT INTO icingaweb_schema (version, timestamp, success, reason)
  VALUES('2.13.0', EXTRACT(EPOCH FROM now()) * 1000, 'y', NULL);
