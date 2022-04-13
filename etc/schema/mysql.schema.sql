# Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+

CREATE TABLE `icingaweb_group`(
  `id`     int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`   varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `parent` int(10) unsigned NULL DEFAULT NULL,
  `ctime`  timestamp NULL DEFAULT NULL,
  `mtime`  timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  CONSTRAINT `fk_icingaweb_group_parent_id` FOREIGN KEY (`parent`)
    REFERENCES `icingaweb_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `icingaweb_group_membership`(
  `group_id`   int(10) unsigned NOT NULL,
  `username`   varchar(254) COLLATE utf8_unicode_ci NOT NULL,
  `ctime`      timestamp NULL DEFAULT NULL,
  `mtime`      timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`username`),
  CONSTRAINT `fk_icingaweb_group_membership_icingaweb_group` FOREIGN KEY (`group_id`)
    REFERENCES `icingaweb_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `icingaweb_user`(
  `name`          varchar(254) COLLATE utf8_unicode_ci NOT NULL,
  `active`        tinyint(1) NOT NULL,
  `password_hash` varbinary(255) NOT NULL,
  `ctime`         timestamp NULL DEFAULT NULL,
  `mtime`         timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `icingaweb_user_preference`(
  `username` varchar(254) COLLATE utf8_unicode_ci NOT NULL,
  `section`  varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name`     varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `value`    varchar(255) NOT NULL,
  `ctime`    timestamp NULL DEFAULT NULL,
  `mtime`    timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`,`section`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;


# Icinga Web 2 Dashboards

CREATE TABLE `icingaweb_dashboard_home` (
    `id`        int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `username`  varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority`  tinyint NOT NULL,
    `type`      enum('public', 'private', 'shared') DEFAULT 'private',
    `disabled`  tinyint DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_dashboard_home_name` (`name`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_dashboard` (
    `id`        binary(20) NOT NULL,
    `home_id`   int(10) UNSIGNED NOT NULL,
    `username`  varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority`  tinyint NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_dashboard_dashboard_home` (`home_id`),
    CONSTRAINT `fk_dashboard_dashboard_home` FOREIGN KEY (`home_id`)
      REFERENCES  `icingaweb_dashboard_home` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_dashlet` (
    `id`            binary(20) NOT NULL,
    `dashboard_id`  binary(20) NOT NULL,
    `name`          varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `url`           varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    `priority`      tinyint NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_dashlet_dashboard` (`dashboard_id`),
    CONSTRAINT `fk_dashlet_dashboard` FOREIGN KEY (`dashboard_id`)
      REFERENCES `icingaweb_dashboard` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_module_dashlet` (
    `id`            binary(20) NOT NULL,
    `name`          varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `module`        varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `pane`          varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `url`           varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    `description`   text DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `priority`      int(10) DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_module_dashlet_name` (`name`),
    INDEX `idx_module_dashlet_pane` (`pane`),
    INDEX `idx_module_dashlet_module` (`module`),
    INDEX `idx_module_dashlet_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `icingaweb_system_dashlet` (
    `id`                int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `dashlet_id`        binary(20) NOT NULL,
    `module_dashlet_id` binary(20) NOT NULL,
    CONSTRAINT `fk_dashlet_system_dashlet` FOREIGN KEY (`dashlet_id`)
      REFERENCES `icingaweb_dashlet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dashlet_system_module_dashlet` FOREIGN KEY (`module_dashlet_id`)
      REFERENCES `icingaweb_module_dashlet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
