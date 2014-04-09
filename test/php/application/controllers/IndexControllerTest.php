<?php

namespace Tests\Application\Controller;

class IndexControllerTest extends \Zend_Test_PHPUnit_ControllerTestCase {
    private $applicationPath;

    public function setUp()
    {
        $this->applicationPath = realpath(__DIR__. '/../../../../application');

        if (!defined('APPLICATION_PATH')) {
            define('APPLICATION_PATH', $this->applicationPath);
        }

        if (!defined('APPLICATION_ENV')) {
            define('APPLICATION_ENV', 'test');
        }

        // Assign and instantiate in one step:
        $this->bootstrap = array($this, 'appBootstrap');

        parent::setUp();
    }

    public function appBootstrap()
    {
        $this->getFrontController()->setControllerDirectory($this->applicationPath. '/controllers');
    }

    public function testIndexAction()
    {
        $this->markTestSkipped('Static can not be detached from bootstrap');
        $this->dispatch('/index/welcome');
        $this->assertController('error');
    }
}
