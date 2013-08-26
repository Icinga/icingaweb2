create table account (
  `username`    varchar(255) COLLATE latin1_general_ci NOT NULL,
  `salt`        varchar(255) NOT NULL,
  `password`    varchar(255) NOT NULL,
  `active`      tinyint(1)   DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*
 *  user:     icingaadmin
 *  password: icinga
 */
INSERT INTO account (
    `username`,
    `salt`,
    `password`,
    `active`
  )
  VALUES (
    'icingaadmin',
    'IepKgTTShC',
    '52deddb5cc7a5769484fcb0fbc5981a7c62cd9f3ddbb8ff3ddb1b89ea324ad16',
    1
  );