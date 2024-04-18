# Icinga Web 2 | (c) 2014 Icinga GmbH | GPLv2+

CREATE TABLE `icingaweb_group`(
  `id`     int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`   varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent` int(10) unsigned NULL DEFAULT NULL,
  `ctime`  timestamp NULL DEFAULT NULL,
  `mtime`  timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  CONSTRAINT `fk_icingaweb_group_parent_id` FOREIGN KEY (`parent`)
    REFERENCES `icingaweb_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE `icingaweb_group_membership`(
  `group_id`   int(10) unsigned NOT NULL,
  `username`   varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ctime`      timestamp NULL DEFAULT NULL,
  `mtime`      timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`username`),
  CONSTRAINT `fk_icingaweb_group_membership_icingaweb_group` FOREIGN KEY (`group_id`)
    REFERENCES `icingaweb_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE `icingaweb_user`(
  `name`          varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active`        tinyint(1) NOT NULL,
  `password_hash` varbinary(255) NOT NULL,
  `ctime`         timestamp NULL DEFAULT NULL,
  `mtime`         timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

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

CREATE TABLE `icingaweb_user_preference`(
  `username` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section`  varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name`     varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value`    varchar(255) NOT NULL,
  `ctime`    timestamp NULL DEFAULT NULL,
  `mtime`    timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`,`section`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

CREATE TABLE `icingaweb_rememberme`(
  id                int(10) unsigned NOT NULL AUTO_INCREMENT,
  username          varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  passphrase        varchar(256) NOT NULL,
  random_iv         varchar(32) NOT NULL,
  http_user_agent   text NOT NULL,
  expires_at        timestamp NULL DEFAULT NULL,
  ctime             timestamp NULL DEFAULT NULL,
  mtime             timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

CREATE TABLE icingaweb_schema (
  id        int unsigned NOT NULL AUTO_INCREMENT,
  version   varchar(64) NOT NULL,
  timestamp bigint unsigned NOT NULL,
  success   enum('n', 'y') DEFAULT NULL,
  reason    text DEFAULT NULL,

  PRIMARY KEY (id),
  CONSTRAINT idx_icingaweb_schema_version UNIQUE (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

INSERT INTO icingaweb_schema (version, timestamp, success)
  VALUES ('2.13.0', UNIX_TIMESTAMP() * 1000, 'y');
