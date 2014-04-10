<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Application\Controller;

use Icinga\Test\BaseTestCase;

class IndexControllerTest extends BaseTestCase
{
    public function setUp()
    {
        if (!defined('APPLICATION_PATH')) {
            define('APPLICATION_PATH', BaseTestCase::$appDir);
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
        $this->getFrontController()->setControllerDirectory(BaseTestCase::$appDir . '/controllers');
    }

    public function testIndexAction()
    {
        $this->markTestSkipped('Static can not be detached from bootstrap');
        $this->dispatch('/index/welcome');
        $this->assertController('error');
    }
}
