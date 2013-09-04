<?php

namespace Tests\Icinga\User\Preferences;

require_once __DIR__. '/../../../../../../library/Icinga/Exception/ConfigurationError.php';
require_once __DIR__. '/../../../../../../library/Icinga/User.php';
require_once __DIR__. '/../../../../../../library/Icinga/User/Preferences.php';
require_once __DIR__. '/../../../../../../library/Icinga/User/Preferences/LoadInterface.php';
require_once __DIR__. '/../../../../../../library/Icinga/User/Preferences/FlushObserverInterface.php';
require_once __DIR__. '/../../../../../../library/Icinga/User/Preferences/IniStore.php';

require_once 'Zend/Config.php';
require_once 'Zend/Config/Writer/Ini.php';

use Icinga\User;
use Icinga\User\Preferences\IniStore;
use \PHPUnit_Framework_TestCase;

class IniStoreTest extends PHPUnit_Framework_TestCase
{
    private $tempDir;

    protected function setUp()
    {
        $tempDir = sys_get_temp_dir();
        $this->tempDir = tempnam($tempDir, 'ini-store-test');
        if (file_exists($this->tempDir)) {
            unlink($this->tempDir);
        }
        mkdir($this->tempDir);
    }

    protected function tearDown()
    {
        if (is_dir($this->tempDir)) {
            system('rm -rf '. $this->tempDir);
        }
    }

    private function createTestConfig()
    {
        $user = new User('jdoe');
        $iniStore = new IniStore($this->tempDir);
        $iniStore->setUser($user);

        $preferences = new User\Preferences(array());
        $preferences->attach($iniStore);

        $preferences->startTransaction();
        $preferences->set('test.key1', 'ok1');
        $preferences->set('test.key2', 'ok2');
        $preferences->set('test.key3', 'ok3');
        $preferences->set('test.key4', 'ok4');
        $preferences->commit();

        return $preferences;
    }

    public function testWritePreferencesToFile()
    {
        $user = new User('jdoe');
        $iniStore = new IniStore($this->tempDir);
        $iniStore->setUser($user);

        $preferences = new User\Preferences(array());
        $preferences->attach($iniStore);

        $preferences->startTransaction();
        $preferences->set('test.key1', 'ok1');
        $preferences->set('test.key2', 'ok2');
        $preferences->set('test.key3', 'ok3');
        $preferences->commit();

        $preferences->remove('test.key2');

        $file = $this->tempDir. '/jdoe.ini';
        $data = (object)parse_ini_file($file);

        $this->assertAttributeEquals('ok1', 'test.key1', $data, 'ini contains test.key1');
        $this->assertAttributeEquals('ok3', 'test.key3', $data, 'ini contains test.key3');
        $this->assertObjectNotHasAttribute('test.key2', $data, 'ini does not contain key test.key2');
    }

    public function testUpdatePreferencesToFile()
    {
        $this->createTestConfig();

        $user = new User('jdoe');
        $iniStore = new IniStore($this->tempDir);
        $iniStore->setUser($user);

        $preferences = new User\Preferences($iniStore->load());
        $preferences->attach($iniStore);

        $preferences->startTransaction();
        $preferences->remove('test.key1');
        $preferences->remove('test.key2');
        $preferences->remove('test.key3');
        $preferences->set('test.key4', 'ok9898');

        $this->assertCount(4, $preferences, 'Before commit we need 4 items');

        $preferences->commit();
        $this->assertCount(1, $preferences, 'After we need 1 item');

        $this->assertEquals('ok9898', $preferences->get('test.key4'), 'After commit preference key has changed');
    }

    public function testLoadInterface()
    {
        $this->createTestConfig();

        $user = new User('jdoe');
        $iniStore = new IniStore($this->tempDir);
        $iniStore->setUser($user);

        $preferences = new User\Preferences($iniStore->load());
        $this->assertEquals('ok4', $preferences->get('test.key4'), 'Test for test.key4');
        $this->assertCount(4, $preferences, 'Count 4 items');
    }

    /**
     * @expectedException Icinga\Exception\ConfigurationError
     * @expectedExceptionMessage Config dir dos not exist: /path/does/not/exist
     */
    public function testInitializationFailure()
    {
        $iniStore = new IniStore('/path/does/not/exist');
    }
}
