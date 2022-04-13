CREATE TABLE `icingaweb_dashboard_home` (
    `id`        int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`      varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `label`     varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    `username`  varchar(254) NOT NULL COLLATE utf8mb4_unicode_ci,
    `priority`  tinyint NOT NULL,
    `disabled`  tinyint DEFAULT 0,
    `type`      enum('public', 'private', 'shared') DEFAULT 'private',
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
