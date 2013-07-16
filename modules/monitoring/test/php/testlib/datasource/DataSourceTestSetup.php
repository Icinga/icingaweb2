<?php

namespace Test\Monitoring\Testlib\DataSource;

require_once(dirname(__FILE__).'/strategies/InsertionStrategy.php');
require_once(dirname(__FILE__).'/strategies/SetupStrategy.php');

require_once(dirname(__FILE__).'/strategies/MySQLSetupStrategy.php');
require_once(dirname(__FILE__).'/strategies/PgSQLSetupStrategy.php');
require_once(dirname(__FILE__).'/strategies/PDOInsertionStrategy.php');
require_once(dirname(__FILE__).'/TestFixture.php');

use \Test\Monitoring\Testlib\Datasource\Strategies\InsertionStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\SetupStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\MySQLSetupStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\PgSQLSetupStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\PDOInsertionStrategy;

class DataSourceTestSetup implements SetupStrategy, InsertionStrategy
{

    private $setupStrategy;
    private $insertionStrategy;

    public function __construct($type)
    {
        if ($type == 'mysql') {
            $this->setupStrategy = new MySQLSetupStrategy();
            $this->insertionStrategy = new PDOInsertionStrategy();
        } elseif ($type == 'pgsql') {
            $this->setupStrategy = new PgSQLSetupStrategy();
            $this->insertionStrategy = new PDOInsertionStrategy();
        } else {
            throw new \Exception('Unsupported backend '.$type);
        }

    }

    public function insert(TestFixture $fixture) {
        $this->insertionStrategy->insert($fixture);
    }

    public function setup($version = null, $connection = null)
    {
        $c = $this->setupStrategy->setup($version, $connection);
        $this->insertionStrategy->setConnection($c);
    }

    public function teardown($connection = null)
    {
        $this->setupStrategy->teardown($connection);
    }

    public function setConnection($connection)
    {
        $this->insertionStrategy->setConnection($connection);
    }

}