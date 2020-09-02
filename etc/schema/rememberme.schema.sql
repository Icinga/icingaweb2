CREATE TABLE icingaweb_rememberme (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    username varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
    public_key text NOT NULL,
    private_key text NOT NULL,
    http_user_agent text NOT NULL,
    expires_at timestamp NULL DEFAULT NULL,
    ctime timestamp NULL DEFAULT NULL,
    mtime timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT idx_rememberme_username UNIQUE KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;


/*EXPERIMENTAL*/

CREATE TABLE icingaweb_rememberme (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    username varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
    public_key text NOT NULL,
    private_key text NOT NULL,
    http_user_agent text NOT NULL,
    expires_at timestamp NULL DEFAULT NULL,
    ctime timestamp NULL DEFAULT NULL,
    mtime timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;


insert into icingaweb_rememberme
(username, public_key, private_key, http_user_agent, expires_at, ctime, mtime)
values('dummy', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'Mac os', DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW());

insert into icingaweb_rememberme
    (username, public_key, private_key, http_user_agent, expires_at, ctime, mtime)
    values('sdhillon', 'thtejdfdfdfdjtn', 'ndejtbbfewhhjeje', 'Iphone 7',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('sdhillon', 'thtejffgfgfggfbcbt4jtn', 'ndejtjegehhjeje', 'ChromeBook',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('sdhillon', 'thtefnghffjjfgfhgnfnfjtn', 'bddbddnejcbe', 'Win10',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('dummy', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'ubuntu',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('dummy', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'ubuntu',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('dummy', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'Mac os',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('Eric', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'Iphone x',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('Eric', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegecwhhjeje', 'ubuntu',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('Eric', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewbchhjeje', 'Mac os',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('dummy2', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'ubuntu',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('dummy2', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'ubuntu',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('dummy2', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'Mac os',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('babbu', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'Iphone 6',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('babbu', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'Chromebook',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW()),
    ('babbu', 'thtejtetheh4jjrjjt4jtn', 'ndejtjegewhhjeje', 'Mac os',  DATE_ADD(NOW(), INTERVAL 31 DAY), NOW(), NOW());

