CREATE TABLE `example_table`(
  `name`        varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value`       text,
  `ctime`       timestamp NULL DEFAULT NULL,
  `mtime`       timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `example_table` VALUES ('a', 'a', NOW(), NOW());
INSERT INTO `example_table` VALUES ('b', 'b', NOW(), NOW());
INSERT INTO `example_table` VALUES ('c', 'c', NOW(), NOW());
INSERT INTO `example_table` VALUES ('d', 'd', NOW(), NOW());
INSERT INTO `example_table` VALUES ('e', 'e', NOW(), NOW());
INSERT INTO `example_table` VALUES ('f', 'f', NOW(), NOW());
INSERT INTO `example_table` VALUES ('g', 'g', NOW(), NOW());
INSERT INTO `example_table` VALUES ('h', 'h', NOW(), NOW());
INSERT INTO `example_table` VALUES ('i', 'i', NOW(), NOW());
INSERT INTO `example_table` VALUES ('j', 'j', NOW(), NOW());
INSERT INTO `example_table` VALUES ('k', 'k', NOW(), NOW());
INSERT INTO `example_table` VALUES ('l', 'l', NOW(), NOW());
INSERT INTO `example_table` VALUES ('m', 'm', NOW(), NOW());
INSERT INTO `example_table` VALUES ('n', 'n', NOW(), NOW());
INSERT INTO `example_table` VALUES ('o', 'o', NOW(), NOW());
INSERT INTO `example_table` VALUES ('p', 'p', NOW(), NOW());
INSERT INTO `example_table` VALUES ('q', 'q', NOW(), NOW());
INSERT INTO `example_table` VALUES ('r', 'r', NOW(), NOW());
INSERT INTO `example_table` VALUES ('s', 's', NOW(), NOW());
INSERT INTO `example_table` VALUES ('t', 't', NOW(), NOW());
INSERT INTO `example_table` VALUES ('u', 'u', NOW(), NOW());
INSERT INTO `example_table` VALUES ('v', 'v', NOW(), NOW());
INSERT INTO `example_table` VALUES ('w', 'w', NOW(), NOW());
INSERT INTO `example_table` VALUES ('x', 'x', NOW(), NOW());
INSERT INTO `example_table` VALUES ('y', 'y', NOW(), NOW());
INSERT INTO `example_table` VALUES ('z', 'z', NOW(), NOW());
