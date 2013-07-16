<?php

namespace Test\Monitoring\Testlib\Datasource\Strategies;

interface SetupStrategy {
    public function setup($version = null, $connection = null);
    public function teardown($connection = null);
}