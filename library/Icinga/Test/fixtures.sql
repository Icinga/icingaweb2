CREATE TABLE `icingaweb_dashboard_owner` (
     `id`        INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
     `username`  VARCHAR NOT NULL,
     UNIQUE(`username`)
);

CREATE TABLE `icingaweb_dashboard_home` (
    `id`        INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `user_id`   INTEGER NOT NULL,
    `name`      VARCHAR NOT NULL,
    `label`     VARCHAR NOT NULL,
    `priority`  tinyint NOT NULL,
    `type`      TEXT CHECK ( `type`  IN ('public', 'private', 'shared') ) DEFAULT 'private',
    `disabled`  TEXT CHECK ( `disabled` IN ('n', 'y') ) DEFAULT 'n',
    UNIQUE(user_id, `name`),
    FOREIGN KEY (user_id) REFERENCES `icingaweb_dashboard_owner` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `icingaweb_dashboard` (
    `id`        binary(20) NOT NULL PRIMARY KEY,
    `home_id`   INTEGER NOT NULL,
    `name`      VARCHAR NOT NULL,
    `label`     VARCHAR NOT NULL,
    `priority`  tinyint NOT NULL,
    FOREIGN KEY (`home_id`) REFERENCES `icingaweb_dashboard_home` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `icingaweb_dashlet` (
     `id`            binary(20) NOT NULL PRIMARY KEY,
     `dashboard_id`  binary(20) NOT NULL,
     `name`          VARCHAR NOT NULL,
     `label`         VARCHAR NOT NULL,
     `url`           VARCHAR NOT NULL,
     `priority`      tinyint NOT NULL,
     `disabled`  TEXT CHECK ( disabled IN ('n', 'y') ) DEFAULT 'n',
     FOREIGN KEY (`dashboard_id`) REFERENCES `icingaweb_dashboard` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `icingaweb_module_dashlet` (
    `id`            binary(20) NOT NULL PRIMARY KEY,
    `name`          VARCHAR NOT NULL,
    `label`         VARCHAR NOT NULL,
    `module`        VARCHAR NOT NULL,
    `pane`          VARCHAR DEFAULT NULL,
    `url`           VARCHAR NOT NULL,
    `description`   text DEFAULT NULL,
    `priority`      tinyint DEFAULT 0
);

CREATE TABLE `icingaweb_system_dashlet` (
    `dashlet_id`        binary(20) NOT NULL PRIMARY KEY,
    `module_dashlet_id` binary(20) DEFAULT NULL,
    FOREIGN KEY (`dashlet_id`) REFERENCES `icingaweb_dashlet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`module_dashlet_id`) REFERENCES `icingaweb_module_dashlet` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
);
