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

# Icinga Web 2 enhanced Dashboards

CREATE TABLE icingaweb_dashboard_owner (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  username varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,

  PRIMARY KEY (id),
  UNIQUE KEY idx_icingaweb_dashboard_user_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_dashboard_home (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  label varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  priority tinyint NOT NULL,
  type enum('public', 'private', 'shared') DEFAULT 'private',
  disabled enum ('n', 'y') DEFAULT 'n',
  PRIMARY KEY (id),
  UNIQUE KEY idx_icingaweb_dashboard_home_user_name (user_id, name),
  CONSTRAINT `fk_dashboard_home_dashboard_user` FOREIGN KEY (`user_id`)
    REFERENCES icingaweb_dashboard_owner (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_dashboard (
  id binary(20) NOT NULL,
  home_id int(10) unsigned NOT NULL,
  name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  label varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  priority tinyint NOT NULL,
  PRIMARY KEY (id),
  KEY fk_dashboard_dashboard_home (home_id),
  CONSTRAINT fk_icingaweb_dashboard_icingaweb_dashboard_home FOREIGN KEY (home_id)
    REFERENCES icingaweb_dashboard_home (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_dashlet (
  id binary(20) NOT NULL,
  dashboard_id binary(20) NOT NULL,
  name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  label varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
  url varchar(2048) NOT NULL COLLATE utf8mb4_bin,
  priority tinyint NOT NULL,
  disabled enum ('n', 'y') DEFAULT 'n',
  description text DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (id),
  KEY `fk_icingaweb_dashlet_icingaweb_dashboard` (`dashboard_id`),
  CONSTRAINT fk_icingaweb_dashlet_icingaweb_dashboard FOREIGN KEY (dashboard_id)
    REFERENCES icingaweb_dashboard (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_module_dashlet (
  id binary(20) NOT NULL,
  name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  label varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  module varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
  pane varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  url varchar(2048) NOT NULL COLLATE utf8mb4_bin,
  description text DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  priority tinyint DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_system_dashlet (
  dashlet_id binary(20) NOT NULL,
  module_dashlet_id binary(20) DEFAULT NULL,

  PRIMARY KEY (dashlet_id),

  CONSTRAINT fk_icingaweb_system_dashlet_icingaweb_dashlet FOREIGN KEY (dashlet_id)
    REFERENCES icingaweb_dashlet (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_icingaweb_system_dashlet_icingaweb_module_dashlet FOREIGN KEY (module_dashlet_id)
    REFERENCES icingaweb_module_dashlet (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE icingaweb_schema (
  id int unsigned NOT NULL AUTO_INCREMENT,
  version smallint unsigned NOT NULL,
  timestamp int unsigned NOT NULL,

  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

INSERT INTO icingaweb_schema (version, timestamp)
  VALUES (6, UNIX_TIMESTAMP());
