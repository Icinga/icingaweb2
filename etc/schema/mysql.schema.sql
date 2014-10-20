CREATE TABLE `icingaweb_group`(
  `name`   varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `parent` varchar(64) COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `ctime`  timestamp NULL DEFAULT NULL,
  `mtime`  timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `icingaweb_group_membership`(
  `group_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `username`   varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `ctime`      timestamp NULL DEFAULT NULL,
  `mtime`      timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_name`,`username`),
  CONSTRAINT `fk_icingaweb_group_membership_icingaweb_group` FOREIGN KEY (`group_name`)
    REFERENCES `icingaweb_group` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `icingaweb_user`(
  `name`          varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `active`        tinyint(1) NOT NULL,
  `password_hash` varbinary(255) NOT NULL,
  `ctime`         timestamp NULL DEFAULT NULL,
  `mtime`         timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `icingaweb_user_preference`(
  `username` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name`     varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `value`    varchar(255) NOT NULL,
  `ctime`    timestamp NULL DEFAULT NULL,
  `mtime`    timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
