<?php

namespace Test\Monitoring\Testlib\Datasource\Strategies;

interface SetupStrategy {
    public function setup($version = null, $resource = null);
    public function teardown($resource = null);
}