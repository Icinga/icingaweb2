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

INSERT INTO dashboard_home (id, name, owner) VALUES
(1, 'Banking', 'jdoe'),
(2, 'Fraud Detection', 'icingaadmin'),
(3, 'Shared Dashboards', default),
(4, 'Available Dashlets', 'icingaadmin');

INSERT INTO dashboard (id, home_id, name) VALUES
(1, 2, 'Current Incidents'),
(2, 1, 'Overdue'),
(3, 4, 'Icinga');

INSERT INTO dashlet (id, dashboard_id, owner, name, url) VALUES
(1, 1, 'icingaadmin', 'Service Problems', '/icingaweb2/monitoring/list/services?service_problem=1&sort=service_severity'),
(2, 1, 'icingaadmin', 'Host Problems', '/icingaweb2/monitoring/list/hosts?host_problem=1&sort=host_severity'),
(3, 2, 'icingaadmin', 'Overdue Services', '/icingaweb2/monitoring/list/services?service_state=0&sort=service_last_state_change'),
(4, 3, 'icingaadmin', 'Icinga Hosts', '/icingaweb2/monitoring/list/hosts?host_state=0&sort=host_last_state_change');


CREATE USER 'dashboard'@'%' IDENTIFIED BY 'dashboard';
GRANT ALL PRIVILEGES ON dashboard.* TO 'dashboard'@'%' IDENTIFIED BY 'dashboard';

FLUSH PRIVILEGES;
