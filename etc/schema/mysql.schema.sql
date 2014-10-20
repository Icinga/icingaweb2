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
  PRIMARY KEY (`group_name`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
