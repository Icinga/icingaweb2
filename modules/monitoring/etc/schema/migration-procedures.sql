# Helper functions and procedures for idempotent schema migrations

DELIMITER //
DROP FUNCTION IF EXISTS index_exists //
CREATE FUNCTION index_exists(
  f_table_name varchar(64),
  f_index_name varchar(64)
)
RETURNS BOOL
BEGIN
  DECLARE index_exists BOOL DEFAULT FALSE;
  SELECT EXISTS (
      SELECT 1
      FROM information_schema.statistics
      WHERE table_schema = SCHEMA()
            AND table_name = f_table_name
            AND index_name = f_index_name
  ) INTO index_exists;
  RETURN index_exists;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS drop_index //
CREATE PROCEDURE drop_index (
  IN p_table_name varchar(64),
  IN p_index_name varchar(64)
)
BEGIN
  IF index_exists(p_table_name, p_index_name)
  THEN
    SET @drop_index_sql = CONCAT('ALTER TABLE `', SCHEMA(), '`.`', p_table_name, '` DROP INDEX `', p_index_name, '`');
    PREPARE stmt FROM @drop_index_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS create_index //
CREATE PROCEDURE create_index (
  IN p_table_name varchar(64),
  IN p_index_name varchar(64),
  IN p_index_columns varchar(512)
)
BEGIN
  IF NOT index_exists(p_table_name, p_index_name)
  THEN
    SET @create_index_sql = CONCAT(
      'ALTER TABLE `', SCHEMA(), '`.`', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_index_columns, ')'
    );
    PREPARE stmt FROM @create_index_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS create_unique_index //
CREATE PROCEDURE create_unique_index (
  IN p_table_name varchar(64),
  IN p_index_name varchar(64),
  IN p_index_columns varchar(512)
)
BEGIN
  IF NOT index_exists(p_table_name, p_index_name)
  THEN
    SET @create_index_sql = CONCAT(
      'ALTER TABLE `', SCHEMA(), '`.`', p_table_name, '` ADD UNIQUE INDEX `', p_index_name, '` (', p_index_columns, ')'
    );
    PREPARE stmt FROM @create_index_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

DELIMITER //
DROP FUNCTION IF EXISTS column_exists //
CREATE FUNCTION column_exists(
  f_table_name varchar(64),
  f_column_name varchar(64)
)
RETURNS BOOL
BEGIN
  DECLARE column_exists BOOL DEFAULT FALSE;
  SELECT EXISTS (
      SELECT 1
      FROM information_schema.columns
      WHERE table_schema = SCHEMA()
            AND table_name = f_table_name
            AND column_name = f_column_name
  ) INTO column_exists;
  RETURN column_exists;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS drop_column //
CREATE PROCEDURE drop_column (
  IN p_table_name varchar(64),
  IN p_column_name varchar(64)
)
BEGIN
  IF column_exists(p_table_name, p_column_name)
  THEN
    SET @drop_column_sql = CONCAT('ALTER TABLE `', SCHEMA(), '`.`', p_table_name, '` DROP COLUMN `', p_column_name, '`');
    PREPARE stmt FROM @drop_column_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS create_column //
CREATE PROCEDURE create_column (
  IN p_table_name varchar(64),
  IN p_column_name varchar(64),
  IN p_column_definition varchar(64)
)
BEGIN
  IF NOT column_exists(p_table_name, p_column_name)
  THEN
    SET @create_column_sql = CONCAT(
      'ALTER TABLE `', SCHEMA(), '`.`', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_column_definition
    );
    PREPARE stmt FROM @create_column_sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END //
DELIMITER ;
