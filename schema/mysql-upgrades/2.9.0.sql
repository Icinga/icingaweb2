CREATE TABLE `icingaweb_rememberme`(
  id                int(10) unsigned NOT NULL AUTO_INCREMENT,
  username          varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  passphrase        varchar(256) NOT NULL,
  random_iv         varchar(24) NOT NULL,
  http_user_agent   text NOT NULL,
  expires_at        timestamp NULL DEFAULT NULL,
  ctime             timestamp NULL DEFAULT NULL,
  mtime             timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
