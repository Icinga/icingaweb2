# Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+

DROP TABLE `icingaweb_group_membership`;
DROP TABLE `icingaweb_group`;

CREATE TABLE `icingaweb_group`(
  `id`     int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`   varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `parent` int(10) unsigned NULL DEFAULT NULL,
  `ctime`  timestamp NULL DEFAULT NULL,
  `mtime`  timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`),
  CONSTRAINT `fk_icingaweb_group_parent_id` FOREIGN KEY (`parent`)
    REFERENCES `icingaweb_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `icingaweb_group_membership`(
  `group_id`   int(10) unsigned NOT NULL,
  `username`   varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `ctime`      timestamp NULL DEFAULT NULL,
  `mtime`      timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`,`username`),
  CONSTRAINT `fk_icingaweb_group_membership_icingaweb_group` FOREIGN KEY (`group_id`)
    REFERENCES `icingaweb_group` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
