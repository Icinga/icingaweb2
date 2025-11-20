CREATE TABLE `icingaweb_2fa` (
  `username` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret`   varchar(255) NOT NULL,
  `ctime`    bigint NULL DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
