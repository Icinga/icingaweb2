<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web
{
    /**
     * Mocked controller base class to avoid the complete
     * Bootstrap dependency of the normally used ModuleActionController
     */
    class ModuleActionController
    {
        /**
         * The view this controller would create
         * @var stdClass
         */
        public $view;

        public $headers = array();

        /**
         * Parameters provided on call
         * @var array
         */
        public $params = array();

        /**
         * _getParam method that normally retrieves GET/POST parameters
         *
         * @param string $param     The parameter name to retrieve
         * @return mixed|bool       The parameter $param or false if it doesn't exist
         */
        public function _getParam($param, $default = null)
        {
            if (!isset($this->params[$param])) {
                return $default;
            }
            return $this->params[$param];
        }

        public function getParam($param, $default = null)
        {
            return $this->_getParam($param, $default);
        }

        public function preserve()
        {
            return $this;
        }

        public function getParams()
        {
            return $this->params;
        }

        /**
         * Sets the backend for this controller which will be used in the action
         *
         * @param $backend
         */
        public function setBackend($backend)
        {
            $this->backend = $backend;
        }

        public function __get($param) {
            return $this;
        }

        public function getHeader($header) {
            if (isset($this->headers[$header])) {
                return $this->headers[$header];
            }
            return null;
        }
    }
}


namespace Test\Monitoring\Testlib
{
    require_once 'Zend/View.php';

    use Icinga\Protocol\Statusdat\Reader;
    use Icinga\Web\ActionController;
    use Test\Monitoring\Testlib\DataSource\TestFixture;
    use Test\Monitoring\Testlib\DataSource\DataSourceTestSetup;
    use Monitoring\Backend\Ido;
    use Monitoring\Backend\Statusdat;
    use \Zend_View;

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
    abstract class MonitoringControllerTest extends \PHPUnit_Framework_TestCase
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
         *  Require necessary libraries on test creation
         *
         *  This is called for every test and assures that all required libraries for the controllers
         *  are loaded. If you need additional dependencies you should overwrite this method, call the parent
         *  and then require your classes
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

            require_once('library/Monitoring/Backend/AbstractBackend.php');

        }

        /**
         * Require all defined IDO queries in this module
         *
         */
        private function requireIDOQueries()
        {
            require_once('library/Monitoring/Backend/Ido.php');
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
            require_once(realpath($this->moduleDir.'/library/Monitoring/Backend/Statusdat.php'));
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
            require_once($module.'library/Monitoring/View/MonitoringView.php');
            $this->requireFolder('library/Monitoring/View/');
        }

        /**
         * Require and set up a controller $controller using the backend type specified at $backend
         *
         * @param string $controller            The name of the controller tu use (must be under monitoring/application/controllers)
         * @param string $backend               The backend to use ('mysql', 'pgsql' or 'statusdat')
         * @return ModuleActionController       The newly created controller
         */
        public function requireController($controller, $backend)
        {
            require_once($this->moduleDir.'/application/controllers/'.$controller.'.php');
            $controllerName = '\Monitoring_'.ucfirst($controller);
            /** @var ActionController $controller */
            $controller = new $controllerName;
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
        public function getBackendFor($type) {
            if ($type == "mysql" || $type == "pgsql") {
                $this->requireIDOQueries();
                return new Ido(new \Zend_Config(array(
                    "dbtype"=> $type,
                    'host'  => "localhost",
                    'user'  => "icinga_unittest",
                    'pass'  => "icinga_unittest",
                    'db'    => "icinga_unittest"
                )));
            } else if ($type == "statusdat") {
                $this->requireStatusDatQueries();
                return new Statusdat(new \Zend_Config(array(
                    'status_file' => '/tmp/teststatus.dat',
                    'objects_file' => '/tmp/testobjects.cache',
                    'no_cache' => true
                )));
            }
        }
    }
}
