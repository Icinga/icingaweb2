# Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+

DROP DATABASE IF EXISTS dashboard;
DROP USER IF EXISTS dashboard;

CREATE DATABASE dashboard;
USE dashboard;

CREATE TABLE `dashboard_user` (
    `id`    int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name`  varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    UNIQUE KEY `idx_dashboard_user_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_group` (
    `id`    int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name`  varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    UNIQUE KEY `idx_dashboard_group_name` (`name`)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_role` (
    `id`    int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `role`  varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    UNIQUE KEY `idx_dashboard_role_name` (`role`)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

###################### Begin dashboard homes #################

CREATE TABLE `dashboard_home` (
    `id`    int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`  varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label` varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_dashboard_home_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `home_member` (
    `home_id`   int(10) UNSIGNED NOT NULL,
    `user_id`   int(10) UNSIGNED NOT NULL,
    `type`      enum('public', 'private', 'shared') DEFAULT 'private',
    `owner`     enum('y', 'n') DEFAULT 'n',
    `disabled`  tinyint(1) DEFAULT 0,
    UNIQUE KEY `idx_home_member_pk` (`home_id`, `user_id`),
    KEY `idx_home_member_dashboard_user_fk` (`user_id`),
    CONSTRAINT `fk_home_member_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_home_member_dashboard_home` FOREIGN KEY (`home_id`) REFERENCES dashboard_home (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_home_order` (
    `home_id`   int(10) UNSIGNED NOT NULL,
    `user_id`   int(10) UNSIGNED NOT NULL,
    `priority`  tinyint UNSIGNED NOT NULL,
    UNIQUE KEY `idx_dashboard_home_order_pk` (`home_id`, `user_id`),
    KEY `idx_dashboard_home_order_dashboard_user_fk` (`user_id`),
    CONSTRAINT `fk_dashboard_home_order_dashboard_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dashboard_home_order_dashboard_home` FOREIGN KEY (`home_id`) REFERENCES dashboard_home (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

############# Begin dashboard panes ################################

CREATE TABLE `dashboard` (
    `id`        binary(20) NOT NULL COMMENT 'sha1(username + home.name + name)',
    `home_id`   int(10) UNSIGNED NOT NULL,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    KEY `fk_dashboard_dashboard_home` (`home_id`),
    CONSTRAINT `fk_dashboard_dashboard_home` FOREIGN KEY (`home_id`) REFERENCES  `dashboard_home` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_member` (
    `dashboard_id`  binary(20) NOT NULL,
    `user_id`       int(10) UNSIGNED NOT NULL,
    `type`          enum('public', 'private', 'shared', 'system') DEFAULT 'private' COMMENT 'To distinguish between system, public and custom created panes',
    `owner`         enum('y', 'n') DEFAULT 'n',
    `write_access`  enum('y', 'n') DEFAULT 'n',
    `removed`       enum('y', 'n') DEFAULT 'n',
    `ctime`         BIGINT UNSIGNED DEFAULT NULL,
    `mtime`         BIGINT UNSIGNED DEFAULT NULL,
    UNIQUE KEY `idx_dashboard_member_pk` (`dashboard_id`, `user_id`),
    KEY `idx_dashboard_member_dashboard_user_fk` (`user_id`),
    CONSTRAINT `fk_dashboard_member_dashboard_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dashboard_member_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES dashboard (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_order` (
    `dashboard_id`  binary(20) NOT NULL,
    `user_id`       int(10) UNSIGNED NOT NULL,
    `priority`      tinyint UNSIGNED NOT NULL,
    UNIQUE KEY `idx_dashboard_member_pk` (`dashboard_id`, `user_id`),
    KEY `idx_dashboard_order_dashboard_user_fk` (`user_id`),
    CONSTRAINT `fk_dashboard_order_dashboard_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dashboard_order_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES dashboard (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_override` (
  `dashboard_id`    binary(20) NOT NULL,
  `user_id`         int(10) UNSIGNED NOT NULL,
  `label`           varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  `disabled`        tinyint(1) DEFAULT 0,
  PRIMARY KEY (`user_id`, `dashboard_id`),
  CONSTRAINT `fk_dashboard_override_dashboard_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

################## Begin dashlets ################################

CREATE TABLE `dashlet` (
    `id`            binary(20) NOT NULL COMMENT 'sha1(username + home.name + dashboard.name + name)',
    `dashboard_id`  binary(20) NOT NULL COMMENT 'sha1(username + home.name + dashboard.name)',
    `name`          varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `url`           varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    PRIMARY KEY (`id`),
    KEY `fk_dashlet_dashboard` (`dashboard_id`),
    CONSTRAINT `fk_dashlet_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES `dashboard` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet_member` (
    `dashlet_id`    binary(20) NOT NULL,
    `user_id`       int(10) UNSIGNED NOT NULL,
    `type`          enum('public', 'private', 'shared') DEFAULT 'private',
    `owner`         enum('y', 'n') DEFAULT 'n',
    UNIQUE KEY `idx_dashlet_member_pk` (`dashlet_id`, `user_id`),
    KEY `idx_dashlet_member_dashboard_user_fk` (`user_id`),
    CONSTRAINT `fk_dashlet_member_dashboard_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dashlet_member_dashlet` FOREIGN KEY (`dashlet_id`) REFERENCES dashlet (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet_override` (
    `dashlet_id`    binary(20) NOT NULL,
    `user_id`       int(10) UNSIGNED NOT NULL,
    `url`           varchar(2048) DEFAULT NULL COLLATE utf8mb4_bin,
    `label`         varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `disabled`      tinyint(1) DEFAULT 0,
    PRIMARY KEY (`user_id`, `dashlet_id`),
    CONSTRAINT `fk_dashlet_override_dashboard_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet_order` (
    `dashlet_id`    binary(20) NOT NULL,
    `user_id`       int(10) UNSIGNED NOT NULL,
    `priority`      tinyint UNSIGNED NOT NULL,
    UNIQUE KEY `idx_dashlet_order_pk` (`dashlet_id`, `user_id`),
    KEY `idx_dashlet_order_dashboard_user_fk` (`user_id`),
    CONSTRAINT `fk_dashlet_order_dashboard_user` FOREIGN KEY (`user_id`) REFERENCES dashboard_user (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `dashlet_order_dashlet` FOREIGN KEY (`dashlet_id`) REFERENCES dashlet (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

########################## Groups and Roles resolution table #######################

CREATE TABLE  `group_role_member` (
    `home_id`       int(10) UNSIGNED DEFAULT NULL,
    `dashboard_id`  binary(20) DEFAULT NULL,
    `dashlet_id`    binary(20) DEFAULT NULL,
    `group_id`      int(10) UNSIGNED DEFAULT NULL,
    `role_id`       int(10) UNSIGNED DEFAULT NULL,
    UNIQUE KEY `idx_group_role_member_pk` (`home_id`, `dashboard_id`, `dashlet_id`, `group_id`, `role_id`),
    KEY `idx_group_role_member_dashboard_group_fk` (`group_id`),
    KEY `idx_group_role_member_dashboard_role_fk` (`role_id`),
    CONSTRAINT `fk_group_role_member_dashboard_home` FOREIGN KEY (`home_id`) REFERENCES dashboard_home (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_group_role_member_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES dashboard (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_group_role_member_dashlet` FOREIGN KEY (`dashlet_id`) REFERENCES dashlet (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_group_role_member_dashboard_group` FOREIGN KEY (`group_id`) REFERENCES dashboard_group (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_group_role_member_dashboard_role` FOREIGN KEY (`role_id`) REFERENCES dashboard_role (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

################ END ###################################

CREATE USER 'dashboard'@'%' IDENTIFIED BY 'dashboard';
GRANT ALL PRIVILEGES ON `dashboard`.* TO 'dashboard'@'%' IDENTIFIED BY 'dashboard';

FLUSH PRIVILEGES;
