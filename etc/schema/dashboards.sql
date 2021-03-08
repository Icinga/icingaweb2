DROP DATABASE IF EXISTS dashboard;
DROP USER IF EXISTS dashboard;

CREATE DATABASE dashboard;
USE dashboard;

CREATE TABLE dashboard_home (
    id int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    owner varchar(254) DEFAULT NULL COLLATE utf8mb4_unicode_ci
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE dashboard (
    id int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    home_id int(10) unsigned NOT NULL,
    name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE dashlet (
    id int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    dashboard_id int(10) unsigned NOT NULL,
    owner varchar(254) DEFAULT NULL COLLATE utf8mb4_unicode_ci,
    name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    url varchar(2048) NOT NULL,
    CONSTRAINT fk_dashlet_dashboard FOREIGN KEY (dashboard_id) REFERENCES dashboard (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE USER 'dashboard'@'%' IDENTIFIED BY 'dashboard';
GRANT ALL PRIVILEGES ON dashboard.* TO 'dashboard'@'%' IDENTIFIED BY 'dashboard';

FLUSH PRIVILEGES;
