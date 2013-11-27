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
    '57cfd5746224be4f60c25d4e8514bec35ad2d01810723a138756b285898e71b2',
    '43f8e0588eb39f1a41383b48def0b1fdc45e79b8f67194cccee4453eb3f4ea13',
    1
  );
