<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Testlib\DataSource;

require_once(dirname(__FILE__).'/strategies/InsertionStrategy.php');
require_once(dirname(__FILE__).'/strategies/SetupStrategy.php');

require_once(dirname(__FILE__).'/strategies/MySQLSetupStrategy.php');
require_once(dirname(__FILE__).'/strategies/PgSQLSetupStrategy.php');
require_once(dirname(__FILE__).'/strategies/PDOInsertionStrategy.php');

require_once(dirname(__FILE__).'/strategies/StatusdatInsertionStrategy.php');
require_once(dirname(__FILE__).'/strategies/StatusdatSetupStrategy.php');

require_once(dirname(__FILE__).'/TestFixture.php');

use \Test\Monitoring\Testlib\Datasource\Strategies\InsertionStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\SetupStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\MySQLSetupStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\PgSQLSetupStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\PDOInsertionStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\StatusdatInsertionStrategy;
use \Test\Monitoring\Testlib\Datasource\Strategies\StatusdatSetupStrategy;

/**
 *  Fascade class that handles creation of test-fixture backends
 *
 *  This class handles the creation and combination of SetupStrategies and InsertionStrategy
 *  when testing controllers/queries with different backends.
 *
 *  Example:
 *  <code>
 *  // TestFixtures contain the objects that should be written for testing
 *  $fixture = new TestFixture();
 *  $fixture->addHost(..)->... // setup fixture
 *
 *  $ds = new DataSourceTestSetup('mysql');
 *  $ds->setup(); // create a blank datasource
 *  $ds->insert($fixture);
 *  </code>
 *
 *
 */
class DataSourceTestSetup implements SetupStrategy, InsertionStrategy
{
    /**
     *  The SetupStrategy that is used on 'setup'
     *  @var \Test\Monitoring\Testlib\Datasource\Strategies\StatusdatSetupStrategy
     */
    private $setupStrategy;

    /**
     *  The InsertionStrategy that is used on 'insert'
     *  @var \Test\Monitoring\Testlib\Datasource\Strategies\StatusdatInsertionStrategy
     */
    private $insertionStrategy;

    /**
     *  Create a DataSource for the backend $type.
     *
     *  On creation, a suitable setup/insert combination will be used
     *  for the provided backend, so the caller needn't to care about which
     *  setup or insertion strategy he wants to use.
     *
     *  @param String $type     The type of the backend (currently 'mysql', 'pgsql' and 'statusdat')
     */
    public function __construct($type)
    {
        if ($type == 'mysql') {
            $this->setupStrategy = new MySQLSetupStrategy();
            $this->insertionStrategy = new PDOInsertionStrategy();
            $this->insertionStrategy->datetimeFormat = "Y-m-d H:i:s";

        } elseif ($type == 'pgsql') {
            $this->setupStrategy = new PgSQLSetupStrategy();
            $this->insertionStrategy = new PDOInsertionStrategy();
            $this->insertionStrategy->datetimeFormat = "Y-m-d H:i:s";
        } elseif ($type == 'statusdat') {
            $this->setupStrategy = new StatusdatSetupStrategy();
            $this->insertionStrategy = new StatusdatInsertionStrategy();
        } else {
            throw new \Exception('Unsupported backend '.$type);
        }
    }

    /**
     *  Insert a testfixture into this datasource
     *
     *  @param TestFixture $fixture The fixture to insert into the datasource
     */
    public function insert(TestFixture $fixture) {
        $this->insertionStrategy->insert($fixture);
    }

    /**
     * Create a blank datasource that can be filled with TestFixtures afterwards
     *
     * @param String $version       An (optional) version to use for creation
     * @param mixed $connection     An (optional) connection to use for this datasource
     */
    public function setup($version = null, $connection = null)
    {
        $c = $this->setupStrategy->setup($version, $connection);

        $this->insertionStrategy->setConnection($c);
    }

    /**
     * Remove all testdata created in this datasource
     *
     * @param mixed $connection     An optional connection to use for clean up
     */
    public function teardown($connection = null)
    {
        $this->setupStrategy->teardown($connection);
    }

    /**
     * Sets the connection to use for writing to this datasource
     *
     * @param mixed $connection     The connection to use. The actual type depends
     *                              on the used backend
     */
    public function setConnection($connection)
    {
        $this->insertionStrategy->setConnection($connection);
    }
}
