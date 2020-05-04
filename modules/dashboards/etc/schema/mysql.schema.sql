DROP TABLE IF EXISTS user_dashlet;
DROP TABLE IF EXISTS  dashlet;
DROP TABLE IF EXISTS user_dashboard;
DROP TABLE IF EXISTS dashboard;

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
    priority int(10) unsigned DEFAULT 1,
    style_width float(10) DEFAULT 33.3,
    CONSTRAINT fk_dashlet_dashboard FOREIGN KEY (dashboard_id) REFERENCES dashboard (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

Create TABLE user_dashboard (
   dashboard_id int(10) unsigned NOT NULL,
   user_name varchar(64) NOT NULL COLLATE utf8mb4_unicode_ci,
   CONSTRAINT fk_user_dashboard_dashboard FOREIGN KEY (dashboard_id) REFERENCES dashboard (id) ON DELETE CASCADE ON UPDATE CASCADE
) Engine=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE user_dashlet (
    dashlet_id int(10) unsigned NOT NULL,
    user_dashboard_id int(10) UNSIGNED NOT NULL,
    CONSTRAINT fk_user_dashlet_user_dashboard FOREIGN KEY (user_dashboard_id) REFERENCES user_dashboard (dashboard_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_dashlet_dashlet FOREIGN KEY (dashlet_id) REFERENCES dashlet (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
