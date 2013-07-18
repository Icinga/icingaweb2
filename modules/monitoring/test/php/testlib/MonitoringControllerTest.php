<?php

namespace Icinga\Web
{
    class ModuleActionController
    {
        public $view;
        public $params = array();

        public function _getParam($param)
        {
            if (!isset($this->params[$param])) {
                return false;
            }
            return $this->params[$param];
        }
        public function setBackend($backend)
        {
            $this->backend = $backend;
        }
    }
}


namespace Test\Monitoring\Testlib
{
    use Icinga\Protocol\Statusdat\Reader;
    use Test\Monitoring\Testlib\DataSource\TestFixture;
    use Test\Monitoring\Testlib\DataSource\DataSourceTestSetup;
    use Monitoring\Backend\Ido;
    use Monitoring\Backend\Statusdat;

    class MonitoringControllerTest extends \PHPUnit_Framework_TestCase
    {
        private $moduleDir = "";
        private $appDir = "";
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

        private function requireIDOQueries()
        {
            require_once('library/Monitoring/Backend/Ido.php');
            $this->requireFolder('library/Monitoring/Backend/Ido/Query');
        }

        private function requireFolder($folder)
        {
            $module = $this->moduleDir;
            $views = scandir($module.$folder);
            foreach ($views as $view) {
                if (!preg_match('/php$/', $view)) {
                    continue;
                }
                require_once($module.$folder."/".$view);
            }
        }

        private function requireStatusDatQueries()
        {
            require_once('library/Monitoring/Backend/Statusdat.php');
            require_once($this->moduleDir.'/library/Monitoring/Backend/Statusdat/Query/Query.php');
            $this->requireFolder('library/Monitoring/Backend/Statusdat');
            $this->requireFolder('library/Monitoring/Backend/Statusdat/Criteria');
            $this->requireFolder('library/Monitoring/Backend/Statusdat/Query');
            $this->requireFolder('library/Monitoring/Backend/Statusdat/DataView');
            $this->requireFolder('library/Monitoring/Backend/Statusdat/DataView');
        }

        private function requireViews()
        {
            $module = $this->moduleDir;
            require_once($module.'library/Monitoring/View/MonitoringView.php');
            $this->requireFolder('library/Monitoring/View/');
        }

        public function requireController($controller, $backend)
        {
            require_once($this->moduleDir.'/application/controllers/'.$controller.'.php');
            $controllerName = '\Monitoring_'.ucfirst($controller);
            $controller = new $controllerName;
            $controller->setBackend($this->getBackendFor($backend));
            return $controller;
        }

        public function setupFixture(TestFixture $fixture, $type)
        {
            $dbInstance =  new DataSourceTestSetup($type);
            $dbInstance->setup();
            $dbInstance->insert($fixture);

        }

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