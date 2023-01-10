ALTER TABLE `icingaweb_group` ROW_FORMAT=DYNAMIC;
ALTER TABLE `icingaweb_group_membership` ROW_FORMAT=DYNAMIC;
ALTER TABLE `icingaweb_user` ROW_FORMAT=DYNAMIC;
ALTER TABLE `icingaweb_user_preference` ROW_FORMAT=DYNAMIC;
ALTER TABLE `icingaweb_rememberme` ROW_FORMAT=DYNAMIC;

ALTER TABLE `icingaweb_group` CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE `icingaweb_group_membership` CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE `icingaweb_user` CONVERT TO CHARACTER SET utf8mb4;
ALTER TABLE `icingaweb_user_preference` CONVERT TO CHARACTER SET utf8mb4;

ALTER TABLE `icingaweb_group`
    MODIFY COLUMN `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `icingaweb_group_membership`
    MODIFY COLUMN `username` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `icingaweb_user`
    MODIFY COLUMN `name` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL;

ALTER TABLE `icingaweb_user_preference`
    MODIFY COLUMN `username` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
    MODIFY COLUMN `section`  varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
    MODIFY COLUMN `name`     varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL;

CREATE TABLE icingaweb_schema (
  id int unsigned NOT NULL AUTO_INCREMENT,
  version smallint unsigned NOT NULL,
  timestamp int unsigned NOT NULL,

  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

INSERT INTO icingaweb_schema (version, timestamp)
  VALUES (6, UNIX_TIMESTAMP());
