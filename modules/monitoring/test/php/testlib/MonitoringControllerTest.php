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
    use Test\Monitoring\Testlib\DataSource\TestFixture;
    use Test\Monitoring\Testlib\DataSource\DataSourceTestSetup;
    use Monitoring\Backend\Ido;

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
            $this->requireQueries();
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
            require_once('library/Monitoring/Backend/Ido.php');
        }

        private function requireQueries()
        {
            $module = $this->moduleDir;
            $views = scandir($module.'library/Monitoring/Backend/Ido/Query');
            foreach ($views as $view) {
                if (!preg_match('/php$/', $view)) {
                    continue;
                }
                require_once($module.'library/Monitoring/Backend/Ido/Query/'.$view);
            }
        }

        private function requireViews()
        {
            $module = $this->moduleDir;
            require_once($module.'library/Monitoring/View/MonitoringView.php');

            $views = scandir($module.'library/Monitoring/View');
            foreach ($views as $view) {
                if (!preg_match('/php$/', $view)) {
                    continue;
                }
                require_once($module.'library/Monitoring/View/'.$view);
            }
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
                return new Ido(new \Zend_Config(array(
                    "dbtype"=> $type,
                    'host'  => "localhost",
                    'user'  => "icinga_unittest",
                    'pass'  => "icinga_unittest",
                    'db'    => "icinga_unittest"
                )));
            }
        }
    }
}