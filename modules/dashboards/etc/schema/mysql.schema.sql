DROP TABLE IF EXISTS  dashlet;
DROP TABLE IF EXISTS user_dashlet;
DROP TABLE IF EXISTS user_dashboard;
DROP TABLE IF EXISTS dashboard;
DROP TABLE IF EXISTS users;

CREATE TABLE dashboard (
    id int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    type varchar(64)
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE dashlet (
    id int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    dashboard_id int(10) unsigned NOT NULL,
    name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    url varchar(2048) NOT NULL,
    CONSTRAINT fk_dashlet_dashboard FOREIGN KEY (dashboard_id) REFERENCES dashboard (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

Create TABLE user_dashboard (
   dashboard_id int(10) unsigned NOT NULL,
   user_name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
   CONSTRAINT fk_user_dashboard_dashboard FOREIGN KEY (dashboard_id) REFERENCES dashboard (id) ON DELETE CASCADE ON UPDATE CASCADE
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE user_dashlet (
    id int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_dashboard_id int(10) unsigned NOT NULL,
    name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
    url varchar(2048) NOT NULL,
    CONSTRAINT fk_user_dashlet_user_dashboard FOREIGN KEY (user_dashboard_id) REFERENCES user_dashboard (dashboard_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
