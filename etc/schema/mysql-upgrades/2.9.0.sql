CREATE TABLE `icingaweb_rememberme`(
  id          int(10) unsigned NOT NULL AUTO_INCREMENT,
  username    varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  public_key  text NOT NULL,
  private_key text NOT NULL,
  expires_at  timestamp NULL DEFAULT NULL,
  ctime       timestamp NULL DEFAULT NULL,
  mtime       timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT idx_rememberme_username UNIQUE KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
