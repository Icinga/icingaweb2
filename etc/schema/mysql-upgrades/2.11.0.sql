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

CREATE TABLE `icingaweb_config_scope`(
    `id`     int(10) unsigned NOT NULL AUTO_INCREMENT,
    `module` varchar(254) NOT NULL DEFAULT 'default',
    `type`   varchar(64) NOT NULL,
    `name`   varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
    `hash`   binary(20) NOT NULL COMMENT 'sha1(all option tuples)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_module_type_name` (`module`, `type`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `icingaweb_config_option`(
    `scope_id` int(10) unsigned NOT NULL,
    `name`   varchar(254) NOT NULL,
    `value`  text DEFAULT NULL,
    UNIQUE KEY `idx_scope_id_name` (`scope_id`, `name`),
    CONSTRAINT `fk_scope_id_config_scope` FOREIGN KEY (`scope_id`)
        REFERENCES `icingaweb_config_scope` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
