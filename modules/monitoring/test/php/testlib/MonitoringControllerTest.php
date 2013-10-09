<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Monitoring\Testlib;

require_once 'Zend/View.php';
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';

use \Zend_View;
use \Zend_Config;
use \Zend_Test_PHPUnit_ControllerTestCase;
use \Icinga\Protocol\Statusdat\Reader;
use \Icinga\Web\Controller\ActionController;
use \Icinga\Application\DbAdapterFactory;
use \Test\Monitoring\Testlib\DataSource\TestFixture;
use \Test\Monitoring\Testlib\DataSource\DataSourceTestSetup;
use Icinga\Module\Monitoring\Backend;
use Icinga\Data\ResourceFactory;

/**
 * Base class for monitoring controllers that loads required dependencies
 * and allows easier setup of tests
 *
 * Example:
 * <code>
 *
 * class MyControllerTest  extends MonitoringControllerTest
 * {
 *      public function testSomething()
 *      {
 *          // Create a test fixture
 *          $fixture = new TestFixture()
 *          $fixture->addHost('host', 0)->addService(...)->..->;
 *
 *          $this->setupFixture($fixture, "mysql"); // setup the fixture
 *          $controller = $this->requireController('MyController', 'mysql');
 *          // controller is now the Zend controller instance, perform an action
 *          $controller->myAction();
 *          $result = $controller->view->hosts->fetchAll();
 *          // assert stuff
 *      }
 * }
 */
abstract class MonitoringControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    /**
     * The module directory for requiring modules (is relative to the source file)
     * @var string
     */
    private $moduleDir = "";

    /**
     * The application directory for requirying library files (is relative to the source file)
     * @var string
     */
    private $appDir = "";

    /**
     * Require necessary libraries on test creation
     *
     * This is called for every test and assures that all required libraries for the controllers
     * are loaded. If you need additional dependencies you should overwrite this method, call the parent
     * and then require your classes
     *
     * @backupStaticAttributes enabled
     */
    public function setUp()
    {
        $this->moduleDir = dirname(__FILE__) . '/../../../';
        $this->appDir = $this->moduleDir.'../../library/Icinga/';
        $module = $this->moduleDir;
        $app = $this->appDir;
        set_include_path(get_include_path().':'.$module);
        set_include_path(get_include_path().':'.$app);

        require_once('Zend/Config.php');
        require_once('Zend/Db.php');
        require_once(dirname(__FILE__) . '/datasource/DataSourceTestSetup.php');

        $this->requireBase();
        $this->requireViews();

        ResourceFactory::setConfig(
            new Zend_Config(array(
                'statusdat-unittest' => array(
                    'type'          => 'statusdat',
                    'status_file'   => '/tmp/teststatus.dat',
                    'objects_file'  => '/tmp/testobjects.cache',
                    'no_cache'      => true
                ),
                'ido-mysql-unittest' => array(
                    'type'     => 'db',
                    'db'       => 'mysql',
                    'host'     => 'localhost',
                    'username' => 'icinga_unittest',
                    'password' => 'icinga_unittest',
                    'dbname'   => 'icinga_unittest'
                ),
                'ido-pgsql-unittest' => array(
                    'type'     => 'db',
                    'db'       => 'mysql',
                    'host'     => 'localhost',
                    'username' => 'icinga_unittest',
                    'password' => 'icinga_unittest',
                    'dbname'   => 'icinga_unittest'
                )
            ))
        );
        Backend::setConfig(
            new Zend_Config(array(
                'statusdat-unittest' => array(
                    'type'     => 'statusdat',
                    'resource' => 'statusdat-unittest'
                ),
                'ido-mysql-unittest' => array(
                    'type'     => 'ido',
                    'resource' => 'ido-mysql-unittest'
                ),
                'ido-pgsql-unittest' => array(
                    'type'     => 'ido',
                    'resource' => 'ido-pgsql-unittest'
                )
            ))
        );
    }

    /**
     * Require base application and data retrieval classes from the Icinga Library
     *
     */
    private function requireBase()
    {
        require_once('Application/Benchmark.php');
        require_once('Data/AbstractQuery.php');
        require_once('Data/DatasourceInterface.php');
        require_once('Data/Db/Connection.php');
        require_once('Data/Db/Query.php');
        require_once('Exception/ProgrammingError.php');
        require_once('Web/Widget/SortBox.php');
        require_once('library/Monitoring/Backend/AbstractBackend.php');
        require_once('library/Monitoring/Backend.php');

    }

    /**
     * Require all defined IDO queries in this module
     *
     */
    private function requireIDOQueries()
    {
        require_once('Application/DbAdapterFactory.php');
        $this->requireFolder('library/Monitoring/Backend/Ido/Query');
    }

    /**
     * Require all php files in the folder $folder
     *
     * @param $folder   The path to the folder containing PHP files
     */
    private function requireFolder($folder)
    {
        $module = $this->moduleDir;
        $views = scandir($module.$folder);
        foreach ($views as $view) {
            if (!preg_match('/php$/', $view)) {
                continue;
            }
            require_once(realpath($module.$folder."/".$view));
        }
    }

    /**
     * Require all views and queries from the statusdat backen
     *
     */
    private function requireStatusDatQueries()
    {
        require_once(realpath($this->moduleDir.'/library/Monitoring/Backend/Statusdat/Query/Query.php'));
        $this->requireFolder('library/Monitoring/Backend/Statusdat');
        $this->requireFolder('library/Monitoring/Backend/Statusdat/Criteria');
        $this->requireFolder('library/Monitoring/Backend/Statusdat/Query');
        $this->requireFolder('library/Monitoring/Backend/Statusdat/DataView');
        $this->requireFolder('library/Monitoring/Backend/Statusdat/DataView');
    }

    /**
     *  Require all (generic) view classes from the monitoring module
     */
    private function requireViews()
    {
        $module = $this->moduleDir;
        require_once($module.'library/Monitoring/View/AbstractView.php');
        $this->requireFolder('library/Monitoring/View/');
    }

    /**
     * Require and set up a controller $controller using the backend type specified at $backend
     *
     * @param string $controller            The name of the controller tu use
     *                                      (must be under monitoring/application/controllers)
     * @param string $backend               The backend to use ('mysql', 'pgsql' or 'statusdat')
     * @return ModuleActionController       The newly created controller
     */
    public function requireController($controller, $backend)
    {
        require_once($this->moduleDir.'/application/controllers/'.$controller.'.php');
        $controllerName = '\Monitoring_'.ucfirst($controller);
        $request = $this->getRequest();
        if ($backend == 'statusdat') {
            $this->requireStatusDatQueries();
            $request->setParam('backend', 'statusdat-unittest');
        } else {
            $this->requireStatusDatQueries();
            $request->setParam('backend', "ido-$backend-unittest");
        }
        /** @var ActionController $controller */
        $controller = new $controllerName(
            $request,
            $this->getResponse(),
            array('noInit' => true)
        );
        $controller->setBackend($this->getBackendFor($backend));

        // Many controllers need a view to work properly
        $controller->view = new Zend_View();

        return $controller;
    }

    /**
     * Create a new backend and insert the given fixture into it
     *
     * @param TestFixture $fixture  The TestFixture to create
     * @param string $type          The type of the backend ('mysql', 'pgsql' or 'statusdat')
     */
    public function setupFixture(TestFixture $fixture, $type)
    {
        $dbInstance =  new DataSourceTestSetup($type);
        $dbInstance->setup();
        $dbInstance->insert($fixture);
    }

    /**
     * Set up and configure a new testbackend for the given type
     *
     * @param  string $type     The type of the backend 'mysql', 'pgsql' or 'statusdat'
     * @return Ido|Statusdat    The newly created backend
     */
    public function getBackendFor($type)
    {
        if ($type == "mysql" || $type == "pgsql") {
            $this->requireIDOQueries();
            $backendConfig = new Zend_Config(array(
                'type'     => 'ido'
            ));
            $resourceConfig = new Zend_Config(
                array(
                    'type'     => 'db',
                    'db'       => $type,
                    'host'     => "localhost",
                    'username' => "icinga_unittest",
                    'password' => "icinga_unittest",
                    'dbname'   => "icinga_unittest"
                )
            );
            return new Backend($backendConfig, $resourceConfig);
        } elseif ($type == "statusdat") {
            $this->requireStatusDatQueries();
            $backendConfig = new Zend_Config(array(
                'type'     => 'statusdat'
            ));
            $resourceConfig = new Zend_Config(
                array(
                    'type'          => 'statusdat',
                    'status_file'   => '/tmp/teststatus.dat',
                    'objects_file'  => '/tmp/testobjects.cache',
                    'no_cache'      => true
                )
            );
            return new Backend(
                $backendConfig,
                $resourceConfig
            );
        }
    }
}
