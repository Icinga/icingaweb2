<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Test;

use Icinga\Data\Db\DbConnection;

interface DbTest
{
    /**
     * PHPUnit provider for mysql
     *
     * @return DbConnection
     */
    public static function mysqlDb();

    /**
     * PHPUnit provider for pgsql
     *
     * @return DbConnection
     */
    public static function pgsqlDb();

    /**
     * PHPUnit provider for oracle
     *
     * @return DbConnection
     */
    public static function oracleDb();

    /**
     * Executes sql file on PDO object
     *
     * @param   DbConnection      $resource
     * @param   string          $filename
     *
     * @return  boolean Operational success flag
     */
    public function loadSql(DbConnection $resource, $filename);

    /**
     * Setup provider for testcase
     *
     * @param   string|DbConnection|null $resource
     */
    public function setupDbProvider($resource);
}
