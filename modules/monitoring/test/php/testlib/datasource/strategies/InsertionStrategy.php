<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Testlib\Datasource\Strategies;
use \Test\Monitoring\Testlib\DataSource\TestFixture;

/**
 * Generic interface for Fixture insertion implementations
 *
 * These implementations can create Icinga-compatible Datatsources
 * from TestFixture classes and are therefore rather free in their
 * implementation
 *
 */
interface InsertionStrategy {
    /**
     * Tell the class to use the given ressource as the
     * connection identifier
     *
     * @param $connection   A generic connection identifier,
     *                      the concrete class depends on the implementation
     */
    public function setConnection($connection);

    /**
     * Insert the passed fixture into the datasource and allow
     * the icinga backends to query it.
     *
     * @param TestFixture $fixture
     */
    public function insert(TestFixture $fixture);
}