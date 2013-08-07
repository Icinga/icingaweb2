<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Testlib\Datasource\Strategies;

/***
 * SetupStrategy implementation for PostgreSQL based IDO-Backends
 *
 * This strategy creates a new PostgreSQL Database and removes old ones
 * if necessary. Per default the database user is icinga_unittest:icinga_unittest
 * and the database to be created is also icinga_unittest.
 **/
class PgSQLSetupStrategy implements SetupStrategy {

    /**
     * Tears down any existing databases and creates a new blank IDO scheme.
     *
     * The database is created according to the passed version (or using the
     * newest version if no version is provided), using the idoPgSQL-%VERSION%.sql
     * underneath the schemes folder.
     * A \PDO Connection can be provided, if not the icinga_unittest default
     * connection will be established and used.
     *
     * @param String $version   An optional version to use as the db scheme
     * @param \PDO $connection  An optional connection to use instead of icinga_unittest
     * @return \PDO             The connection that has been created
     *
     * @throws \PDOException    In case connecting to or creating the database fails
     * @throws \Exception       In case of an invalid/non-existing DB scheme
     */
    public function setup($version = null, $connection = null)
    {
        if ($connection === null) {
            $connection = new \PDO('pgsql:dbname=icinga_unittest', 'icinga_unittest', 'icinga_unittest');
        }

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->teardown($connection);

        // the latest schema doesn't have a suffix, so if no version is given this one is used
        $sqlFileName = 'idoPgSQL'.($version !== null ? '-'.$version : '' ).'.sql';
        $path = realpath(dirname(__FILE__).'/../schemes/'.$sqlFileName);
        if (!file_exists($path)) {
            throw new \Exception('File '.$path.' not found: Could not create scheme for IDO pgsql backend '.($version ? '(version : '.$version.')' :''));
        }
        $connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

        if ($connection->query(file_get_contents($path)) === false) {
            $error = $connection->errorInfo();;
            throw new \PDOException($error[0].' : '.$error[2]);
        }
        return $connection;
    }

    /**
     * Drops all tables from the connection via DROP TABLE
     *
     * @param \PDO $connection  An optional connection to use, if none is
     *                          given the icinga_unittest default will be used
     *
     */
    public function teardown($connection = null)
    {
        if ($connection === null) {
            $connection = new \PDO('pgsql:dbname=icinga_unittest', 'icinga_unittest', 'icinga_unittest');
        }
        $tables = $connection
            ->query('SELECT table_schema,table_name FROM information_schema.tables WHERE table_type = \'BASE TABLE\''.
                    'AND table_schema = \'public\' ORDER BY table_schema,table_name;')
            ->fetchAll();

        foreach($tables as $table) {
            $connection->query('DROP TABLE '.$table['table_name']);
        }
    }
}
