CREATE TABLE icingaweb_role (
  id           int unsigned NOT NULL AUTO_INCREMENT,
  parent_id    int unsigned DEFAULT NULL,
  name         varchar(254) NOT NULL,
  unrestricted enum('n', 'y') NOT NULL DEFAULT 'n',
  ctime        bigint unsigned NOT NULL,
  mtime        bigint unsigned DEFAULT NULL,

  PRIMARY KEY (id),
  CONSTRAINT fk_icingaweb_role_parent_id FOREIGN KEY (parent_id)
    REFERENCES icingaweb_role (id) ON DELETE SET NULL,
  CONSTRAINT idx_icingaweb_role_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_role_user (
  role_id   int unsigned NOT NULL,
  user_name varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,

  PRIMARY KEY (user_name, role_id),
  CONSTRAINT fk_icingaweb_role_user_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_role_group (
  role_id    int unsigned NOT NULL,
  group_name varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,

  PRIMARY KEY (group_name, role_id),
  CONSTRAINT fk_icingaweb_role_group_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_role_permission (
  role_id       int unsigned NOT NULL,
  permission    varchar(254) NOT NULL,
  allowed       enum('n', 'y') NOT NULL DEFAULT 'n',
  denied        enum('n', 'y') NOT NULL DEFAULT 'n',

  PRIMARY KEY (role_id, permission),
  CONSTRAINT fk_icingaweb_role_permission_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_role_restriction (
  role_id       int unsigned NOT NULL,
  restriction   varchar(254) NOT NULL,
  filter        text NOT NULL,

  PRIMARY KEY (role_id, restriction),
  CONSTRAINT fk_icingaweb_role_restriction_role_id FOREIGN KEY (role_id)
    REFERENCES icingaweb_role (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

INSERT INTO icingaweb_schema (version, timestamp, success, reason)
  VALUES('2.13.0', UNIX_TIMESTAMP() * 1000, 'y', NULL);
