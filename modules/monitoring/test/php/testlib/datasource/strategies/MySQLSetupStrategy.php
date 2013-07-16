<?php

namespace Test\Monitoring\Testlib\Datasource\Strategies;

class MySQLSetupStrategy implements SetupStrategy {

    public function setup($version = null, $connection = null)
    {
        if ($connection === null) {
            $connection = new \PDO("mysql:dbname=icinga_unittest", "icinga_unittest", "icinga_unittest");
        }

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->teardown($connection);

        // the latest schema doesn't have a suffix, so if no version is given this one is used
        $sqlFileName = 'idoMySQL'.($version !== null ? '-'.$version : '' ).'.sql';
        $path = realpath(dirname(__FILE__).'/../schemes/'.$sqlFileName);
        if (!file_exists($path)) {
            throw new \Exception('File '.$path.' not found: Could not create scheme for IDO mysql backend '.($version ? '(version : '.$version.')' :''));
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
            $connection = new \PDO("mysql:dbname=icinga_unittest", "icinga_unittest", "icinga_unittest");
        }
        echo "teardown";

        $tables = $connection->query("SHOW TABLES")->fetchAll();
        foreach($tables as $table) {
            $connection->query("DROP TABLE ".$table[0]);
        }
    }
}