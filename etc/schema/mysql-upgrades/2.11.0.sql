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
