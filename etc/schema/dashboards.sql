DROP DATABASE IF EXISTS dashboard;
DROP USER IF EXISTS dashboard;

CREATE DATABASE dashboard;
USE dashboard;

CREATE TABLE `dashboard_home` (
    `id`        int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `username`  varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority`  tinyint NOT NULL,
    `type`      enum('public', 'private', 'shared') DEFAULT 'private',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_dashboard_home_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard` (
    `id`        binary(20) NOT NULL,
    `home_id`   int(10) UNSIGNED NOT NULL,
    `username`  varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority`  tinyint NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_dashboard_dashboard_home` (`home_id`),
    CONSTRAINT `fk_dashboard_dashboard_home` FOREIGN KEY (`home_id`) REFERENCES  `dashboard_home` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_override` (
    `dashboard_id`  binary(20) NOT NULL,
    `username`      varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `disabled`      tinyint(1) DEFAULT 0,
    `priority`      tinyint NOT NULL,
    PRIMARY KEY (`dashboard_id`, `username`),
    CONSTRAINT `fk_dashboard_override_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES dashboard (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet` (
    `id`            binary(20) NOT NULL,
    `dashboard_id`  binary(20) NOT NULL,
    `name`          varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `url`           varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    `priority`      tinyint NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_dashlet_dashboard` (`dashboard_id`),
    CONSTRAINT `fk_dashlet_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES `dashboard` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet_system` (
    `dashlet_id`        binary(20) NOT NULL,
    `module_dashlet_id` binary(20) NOT NULL,
    `username`          varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`username`, `dashlet_id`, `module_dashlet_id`),
    CONSTRAINT `fk_dashlet_system_dashlet` FOREIGN KEY (`dashlet_id`) REFERENCES `dashlet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dashlet_system_module_dashlet` FOREIGN KEY (`dashlet_id`) REFERENCES `dashlet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `module_dashlet` (
    `id`            binary(20) NOT NULL,
    `name`          varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`         varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `module`        varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `pane`          varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `url`           varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    `description`   varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `priority`      int(10) DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_module_dashlet_name` (`name`),
    INDEX `idx_module_dashlet_pane` (`pane`),
    INDEX `idx_module_dashlet_module` (`module`),
    INDEX `idx_module_dashlet_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE USER 'dashboard'@'%' IDENTIFIED BY 'dashboard';
GRANT ALL PRIVILEGES ON `dashboard`.* TO 'dashboard'@'%' IDENTIFIED BY 'dashboard';

FLUSH PRIVILEGES;