DROP TABLE rememberme;
CREATE TABLE rememberme (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    username varchar(30) NOT NULL,
    public_key text NOT NULL,
    private_key text NOT NULL,
    last_seen timestamp NULL DEFAULT NULL,
    ctime timestamp NULL DEFAULT NULL,
    mtime timestamp NULL DEFAULT NULL,
    PRIMARY KEY ( id ) ,
    UNIQUE KEY ( username )
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
