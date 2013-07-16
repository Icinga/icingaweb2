<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 7/16/13
 * Time: 3:30 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Test\Monitoring\Testlib\Datasource\Strategies;


class PgSQLSetupStrategy {
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