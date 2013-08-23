<?php

namespace Tests\Icinga\User\Preferences;

require_once __DIR__ . '/../../../../../../library/Icinga/Exception/ConfigurationError.php';
require_once __DIR__ . '/../../../../../../library/Icinga/Util/ConfigAwareFactory.php';
require_once __DIR__ . '/../../../../../../library/Icinga/Application/DbAdapterFactory.php';
require_once __DIR__ . '/../../../../../../library/Icinga/User.php';
require_once __DIR__ . '/../../../../../../library/Icinga/User/Preferences.php';
require_once __DIR__ . '/../../../../../../library/Icinga/User/Preferences/ChangeSet.php';
require_once __DIR__ . '/../../../../../../library/Icinga/User/Preferences/LoadInterface.php';
require_once __DIR__ . '/../../../../../../library/Icinga/User/Preferences/FlushObserverInterface.php';
require_once __DIR__ . '/../../../../../../library/Icinga/User/Preferences/DbStore.php';

require_once 'Zend/Db.php';
require_once 'Zend/Config.php';
require_once 'Zend/Db/Adapter/Abstract.php';

use Icinga\Application\DbAdapterFactory;
use Icinga\User;
use Icinga\User\Preferences\DbStore;
use Icinga\User\Preferences;
use \PHPUnit_Framework_TestCase;
use \Zend_Config;
use \Zend_Db;
use \Zend_Db_Adapter_Abstract;
use \PDOException;
use \Exception;

class DbStoreTest extends PHPUnit_Framework_TestCase
{
    const TYPE_MYSQL = 'mysql';

    const TYPE_PGSQL = 'pgsql';

    private $table = 'preference';

    private $databaseConfig = array(
        'type'     => 'db',
        'host'     => '127.0.0.1',
        'username' => 'icinga_unittest',
        'password' => 'icinga_unittest',
        'dbname'   => 'icinga_unittest'
    );

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $dbMysql;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $dbPgsql;

    private function createDb($type)
    {
        $this->databaseConfig['db'] = $type;
        $db = DbAdapterFactory::createDbAdapterFromConfig(
            new Zend_Config($this->databaseConfig)
        );

        try {
            $db->getConnection();

            $dumpFile = realpath(__DIR__ . '/../../../../../../etc/schema/preferences.' . strtolower($type) . '.sql');

            if (!$dumpFile) {
                throw new Exception('Dumpfile for db type not found: ' . $type);
            }

            try {
                $db->getConnection()->exec(file_get_contents($dumpFile));
            } catch (PDOException $e) {
                // PASS
            }

        } catch (\Zend_Db_Adapter_Exception $e) {
            return null;
        } catch (PDOException $e) {
            return null;
        }

        return $db;
    }

    protected function setUp()
    {
        $this->dbMysql = $this->createDb(self::TYPE_MYSQL);
        $this->dbPgsql = $this->createDb(self::TYPE_PGSQL);
    }

    protected function tearDown()
    {
        if ($this->dbMysql) {
            $this->dbMysql->getConnection()->exec('DROP TABLE ' . $this->table);
        }

        if ($this->dbPgsql) {
            $this->dbPgsql->getConnection()->exec('DROP TABLE ' . $this->table);
        }

    }

    private function createDbStore(Zend_Db_Adapter_Abstract $db)
    {
        $user = new User('jdoe');

        $store = new DbStore();
        $store->setDbAdapter($db);
        $store->setUser($user);

        return $store;
    }

    public function testCreateUpdateDeletePreferenceValuesMySQL()
    {
        if ($this->dbMysql) {
            $store = $this->createDbStore($this->dbMysql);

            $preferences = new Preferences(array());
            $preferences->attach($store);

            $preferences->set('test.key1', 'OK1');
            $preferences->set('test.key2', 'OK2');
            $preferences->set('test.key3', 'OK2');

            $preferences->remove('test.key2');

            $preferences->set('test.key3', 'OKOK333');

            $preferencesTest = new Preferences($store->load());
            $this->assertEquals('OK1', $preferencesTest->get('test.key1'));
            $this->assertNull($preferencesTest->get('test.key2'));
            $this->assertEquals('OKOK333', $preferencesTest->get('test.key3'));
        } else {
            $this->markTestSkipped('MySQL test environment is not configured');
        }
    }

    public function testCreateUpdateDeletePreferenceValuesPgSQL()
    {
        if ($this->dbPgsql) {
            $store = $this->createDbStore($this->dbPgsql);

            $preferences = new Preferences(array());
            $preferences->attach($store);

            $preferences->set('test.key1', 'OK1');
            $preferences->set('test.key2', 'OK2');
            $preferences->set('test.key3', 'OK2');

            $preferences->remove('test.key2');

            $preferences->set('test.key3', 'OKOK333');

            $preferencesTest = new Preferences($store->load());
            $this->assertEquals('OK1', $preferencesTest->get('test.key1'));
            $this->assertNull($preferencesTest->get('test.key2'));
            $this->assertEquals('OKOK333', $preferencesTest->get('test.key3'));
        } else {
            $this->markTestSkipped('PgSQL test environment is not configured');
        }
    }
}