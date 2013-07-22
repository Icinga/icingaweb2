<?php

namespace Test\Monitoring\Testlib\Datasource\Strategies;
/**
 *  Interface for setup classes that provide a clean starting point
 *  for @see InsertionStrategy classes to setup TestFixtures.
 *
 *  As the backend for the setupstrategy can be anything from a database to
 *  a file to a socket, the resource is a mixed php type and the
 *  concrete requirement on the type is defined by the subclass implementing
 *  this interface.
 *
 */
interface SetupStrategy {

    /**
     * Set up a new clean datastore icinga backends can use for querying data
     *
     * The resource parameter should be optional, as the setup class should provide
     * a default setup method most testing setups can use
     *
     * @param String $version   The optional version of the storage implementation to create
     * @param mixed $resource   The optional resource to use for setting up the storage
     *
     * @return mixed            The connection or resource that has been created
     */
    public function setup($version = null, $resource = null);

    /**
     * Remove all data from the given resource (or the default resource if none is given)
     *
     * @param mixed $resource
     */
    public function teardown($resource = null);
}