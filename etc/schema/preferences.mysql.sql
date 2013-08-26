create table `preference`(
  `username`    VARCHAR(255) COLLATE latin1_general_ci NOT NULL,
  `key`         VARCHAR(100) COLLATE latin1_general_ci NOT NULL,
  `value`       VARCHAR(255) NOT NULL,
  PRIMARY KEY (`username`, `key`)
) ENGINE=InnoDB;