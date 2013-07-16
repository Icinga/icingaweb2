<?php

namespace Test\Monitoring\Testlib\Datasource\Strategies;
use \Test\Monitoring\Testlib\DataSource\TestFixture;


interface InsertionStrategy {
    public function setConnection($connection);
    public function insert(TestFixture $fixture);
}