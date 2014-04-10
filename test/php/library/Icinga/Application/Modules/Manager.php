<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Application\Module\Manager;

use Icinga\Test\BaseTestCase;
use Icinga\Application\Modules\Manager as ModuleManager;

class ModuleMock
{
    public $name = "";
    public $dir = "";

    public function __construct($app, $name, $dir)
    {
        $this->name = $name;
        $this->dir = $dir;
    }

    public function register()
    {
    }
}

class ManagerTest extends BaseTestCase
{
    const MODULE_TARGET = "/tmp";

    protected function setUp()
    {
        $moduleDir = self::MODULE_TARGET;
        if (!is_writable($moduleDir)) {
            $this->markTestSkipped("Temporary folder not writable for this user");
            return;
        }
        if (is_dir($moduleDir . "/enabledModules")) {
            exec("rm -r $moduleDir/enabledModules");
        }

        mkdir($moduleDir . "/enabledModules");
    }

    public function testDetectEnabledModules()
    {
        $manager = new ModuleManager(null, "/tmp/enabledModules", array("none"));
        $this->assertEmpty($manager->listEnabledModules());

        symlink(getcwd() . "/res/testModules/module1", "/tmp/enabledModules/module1");
        $manager = new ModuleManager(null, "/tmp/enabledModules", array("none"));
        $this->assertEquals(array("module1"), $manager->listEnabledModules());
        symlink(getcwd() . "/res/testModules/module2", "/tmp/enabledModules/module2");
        symlink(getcwd() . "/res/???", "/tmp/enabledModules/module3");
        $manager = new ModuleManager(null, "/tmp/enabledModules", array("none"));
        $this->assertEquals(array("module1", "module2"), $manager->listEnabledModules());
    }

    public function testLoadModule()
    {
        $manager = new ModuleManager(null, "/tmp/enabledModules", array("./res/testModules"));
                $this->assertEmpty($manager->getLoadedModules());
                $manager->loadModule("module1", "Tests\Icinga\Application\Module\Manager\ModuleMock");
        $elems = $manager->getLoadedModules();
        $this->assertNotEmpty($elems);
        $this->assertTrue(isset($elems["module1"]));
        // assert the changes not to be permanent:
        $manager = new ModuleManager(null, "/tmp/enabledModules", array("./res/testModules"));
        $this->assertEmpty($manager->getLoadedModules());
    }

    public function testEnableModule()
    {
        $manager = new ModuleManager(null, "/tmp/enabledModules", array(getcwd() . "/res/testModules"));
        $this->assertEmpty($manager->listEnabledModules());
        $manager->enableModule("module1");
        $elems = $manager->listEnabledModules();
        $this->assertNotEmpty($elems);
        $this->assertEquals($elems[0], "module1");
        $this->assertTrue(is_link("/tmp/enabledModules/module1"));
        // assert the changes to be permanent:
        $manager = new ModuleManager(null, "/tmp/enabledModules", array("./res/testModules"));
        $this->assertNotEmpty($manager->listEnabledModules());
    }

    public function testDisableModule()
    {
        clearstatcache(true);
        symlink(getcwd() . "/res/testModules/module1", "/tmp/enabledModules/module1");
        $manager = new ModuleManager(null, "/tmp/enabledModules", array(getcwd() . "/res/testModules"));
        $elems = $manager->listEnabledModules();
        $this->assertNotEmpty($elems);
        $this->assertEquals($elems[0], "module1");
        $manager->disableModule("module1");
        $this->assertFalse(file_exists("/tmp/enabledModules/module1"));
        $this->assertEmpty($manager->listEnabledModules());
        // assert the changes to be permanent:
        $manager = new ModuleManager(null, "/tmp/enabledModules", array("./res/testModules"));
        $this->assertEmpty($manager->listEnabledModules());
    }

    protected function tearDown()
    {
        $moduleDir = self::MODULE_TARGET;
        exec("rm -r $moduleDir/enabledModules");
    }
}
