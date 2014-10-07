create table account (
  `username`    varchar(255) COLLATE latin1_general_ci NOT NULL,
  `salt`        varchar(255) NOT NULL,
  `password`    varchar(255) NOT NULL,
  `active`      tinyint(1)   DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

create table `preference`(
  `username`    VARCHAR(255) COLLATE latin1_general_ci NOT NULL,
  `key`         VARCHAR(100) COLLATE latin1_general_ci NOT NULL,
  `value`       VARCHAR(255) NOT NULL,
  PRIMARY KEY (`username`, `key`)
) ENGINE=InnoDB;