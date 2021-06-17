DROP DATABASE IF EXISTS dashboard;
DROP USER IF EXISTS dashboard;

CREATE DATABASE dashboard;
USE dashboard;

CREATE TABLE `dashboard_home` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `owner` varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label` varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `disabled` tinyint(1) DEFAULT 0,
    UNIQUE `unique_indexes` (`name`, `owner`) COMMENT 'Only as a combined entity should be uniquely identified',
    PRIMARY KEY (`id`)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard` (
    `id` binary(20) NOT NULL COMMENT 'sha1(username + home.name + name)',
    `home_id` int(10) UNSIGNED NOT NULL,
    `owner` varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `name` varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label` varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `source` enum('system', 'private') DEFAULT 'private' COMMENT 'To distinguish between cloned system and custom created panes',
    PRIMARY KEY (`id`),
    KEY `fk_dashboard_dashboard_home` (`home_id`),
    CONSTRAINT `fk_dashboard_dashboard_home` FOREIGN KEY (`home_id`) REFERENCES  `dashboard_home` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_order` (
    `dashboard_id` binary(20) NOT NULL,
    `home_id` int(10) UNSIGNED NOT NULL,
    `owner` varchar(254)NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority` tinyint UNSIGNED NOT NULL,
    KEY `fk_dashboard_order_dashboard` (`dashboard_id`),
    CONSTRAINT `fk_dashboard_order_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES dashboard (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashboard_override` (
  `dashboard_id` binary(20) NOT NULL,
  `owner` varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
  `home_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
  `disabled` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`dashboard_id`, `owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet` (
    `id` binary(20) NOT NULL COMMENT 'sha1(username + home.name + dashboard.name + name)',
    `dashboard_id` binary(20) NOT NULL COMMENT 'sha1(username + home.name + name)',
    `owner` varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `name` varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label` varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `url` varchar(2048) NOT NULL COLLATE utf8mb4_bin,
    PRIMARY KEY (`id`),
    KEY `fk_dashlet_dashboard` (`dashboard_id`),
    CONSTRAINT `fk_dashlet_dashboard` FOREIGN KEY (`dashboard_id`) REFERENCES `dashboard` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet_order` (
    `dashlet_id` binary(20) NOT NULL,
    `dashboard_id` binary(20) NOT NULL,
    `owner` varchar(254)NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority` tinyint UNSIGNED NOT NULL,
    KEY `dashlet_order_dashlet` (`dashlet_id`),
    CONSTRAINT `dashlet_order_dashlet` FOREIGN KEY (`dashlet_id`) REFERENCES dashlet (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `dashlet_override` (
    `dashlet_id` binary(20) NOT NULL,
    `dashboard_id` binary(20) NOT NULL,
    `owner` varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `url` varchar(2048) DEFAULT NULL COLLATE utf8mb4_bin,
    `label` varchar(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    `disabled` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`dashlet_id`, `dashboard_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE USER 'dashboard'@'%' IDENTIFIED BY 'dashboard';
GRANT ALL PRIVILEGES ON `dashboard`.* TO 'dashboard'@'%' IDENTIFIED BY 'dashboard';

FLUSH PRIVILEGES;
