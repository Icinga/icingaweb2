<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

use \PDO;
use \Zend_Db_Adapter_Pdo_Abstract;
use \Zend_Config;
use Icinga\Test\BaseTestCase;
use Icinga\Authentication\Backend\DbUserBackend;

/**
 * Test Class fpr DbUserBackend
 */
class DbUserBackendTest extends BaseTestCase
{
    const USER_NAME_COLUMN = 'username';

    const SALT_COLUMN      = 'salt';

    const PASSWORD_COLUMN  = 'password';

    const ACTIVE_COLUMN    = 'active';

    /**
     * The table that is used to store the authentication data
     *
     * @var string
     */
    private $testTable = 'account';

    /**
     * Example users
     *
     * @var array
     */
    private $userData = array(
        array(
            self::USER_NAME_COLUMN  => 'user1',
            self::PASSWORD_COLUMN   => 'secret1',
            self::SALT_COLUMN       => '8a7487a539c5d1d6766639d04d1ed1e6',
            self::ACTIVE_COLUMN     => 1
        ),
        array(
            self::USER_NAME_COLUMN  => 'user2',
            self::PASSWORD_COLUMN   => 'secret2',
            self::SALT_COLUMN       => '04b5521ddd761b5a5b633be83faa494d',
            self::ACTIVE_COLUMN     => 1
        ),
        array(
            self::USER_NAME_COLUMN  => 'user3',
            self::PASSWORD_COLUMN   => 'secret3',
            self::SALT_COLUMN       => '08bb94ba3120338ae56db80ef551d324',
            self::ACTIVE_COLUMN     => 0
        )
    );

    private function createDbBackendConfig($resource, $name = null)
    {
        if ($name === null) {
            $name = 'TestDbUserBackend-' . uniqid();
        }

        $config = new Zend_Config(
            array(
                'name'      => $name,
                'resource'  => $resource
            )
        );

        return $config;
    }

    /**
     * Test the authentication functions of the DbUserBackend using PostgreSQL as backend.
     *
     * @dataProvider pgsqlDb
     */
    public function testCorrectUserLoginForPgsql($db)
    {
        $this->setupDbProvider($db);
        $backend = new DbUserBackend($this->createDbBackendConfig($db));
        $backend->connect();
        $this->runBackendAuthentication($backend);
        $this->runBackendUsername($backend);
    }

    /**
     * Test the authentication functions of the DbUserBackend using MySQL as backend.
     *
     * @dataProvider mysqlDb
     */
    public function testCorrectUserLoginForMySQL($db)
    {
        $this->setupDbProvider($db);
        $backend = new DbUserBackend($this->createDbBackendConfig($db));
        $backend->connect();
        $this->runBackendAuthentication($backend);
        $this->runBackendUsername($backend);
    }

    /**
     * @param Zend_Db_Adapter_Pdo_Abstract $resource
     */
    public function setupDbProvider($resource)
    {
        parent::setupDbProvider($resource);

        $type = $resource->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

        $dumpFile = BaseTestCase::$etcDir . '/schema/accounts.' . $type . '.sql';

        $this->assertFileExists($dumpFile);

        $this->loadSql($resource, $dumpFile);

        for ($i = 0; $i < count($this->userData); $i++) {
            $usr = $this->userData[$i];
            $data = array(
                self::USER_NAME_COLUMN  => $usr[self::USER_NAME_COLUMN],
                self::PASSWORD_COLUMN   => hash_hmac(
                    'sha256',
                    $usr[self::PASSWORD_COLUMN],
                    $usr[self::SALT_COLUMN]
                ),
                self::ACTIVE_COLUMN => $usr[self::ACTIVE_COLUMN],
                self::SALT_COLUMN   => $usr[self::SALT_COLUMN]
            );
            $resource->insert($this->testTable, $data);
        }
    }

    /**
     * Run the hasUsername test against an instance of DbUserBackend
     *
     * @param DbUserBackend $backend The backend that will be tested.
     */
    private function runBackendUsername($backend)
    {
        $this->markTestSkipped('I do not know where Credential is located');
        // Known user
        $this->assertTrue(
            $backend->hasUsername(
                new Credential(
                    $this->userData[0][self::USER_NAME_COLUMN],
                    $this->userData[0][self::PASSWORD_COLUMN]
                )
            ),
            'Assert that the user is known by the backend'
        );

        // Unknown user
        $this->assertFalse(
            $backend->hasUsername(
                new Credential(
                    'unknown user',
                    'secret'
                )
            ),
            'Assert that the user is not known by the backend'
        );

        // Inactive user
        $this->assertFalse(
            $backend->hasUsername(
                new Credential(
                    $this->userData[2][self::USER_NAME_COLUMN],
                    $this->userData[2][self::PASSWORD_COLUMN]
                )
            ),
            'Assert that the user is inactive and therefore not known by the backend'
        );
    }

    /**
     * Run the authentication test against an instance of DbUserBackend
     *
     * @param DbUserBackend $backend The backend that will be tested.
     */
    private function runBackendAuthentication($backend)
    {
        $this->markTestSkipped('I do not know where Credential is located');
        // Known user
        $this->assertNotNull(
            $backend->authenticate(
                new Credential(
                    $this->userData[0][self::USER_NAME_COLUMN],
                    $this->userData[0][self::PASSWORD_COLUMN]
                )
            ),
            'Assert that an existing, active user with the right credentials can authenticate.'
        );

        // Wrong password
        $this->assertNull(
            $backend->authenticate(
                new Credential(
                    $this->userData[1][self::USER_NAME_COLUMN],
                    'wrongpassword'
                )
            ),
            'Assert that an existing user with an invalid password cannot authenticate'
        );

        // Nonexisting user
        $this->assertNull(
            $backend->authenticate(
                new Credential(
                    'nonexisting user',
                    $this->userData[1][self::PASSWORD_COLUMN]
                )
            ),
            'Assert that a non-existing user cannot authenticate.'
        );

        // Inactive user
        $this->assertNull(
            $backend->authenticate(
                new Credential(
                    $this->userData[2][self::USER_NAME_COLUMN],
                    $this->userData[2][self::PASSWORD_COLUMN]
                )
            ),
            'Assert that an inactive user cannot authenticate.'
        );
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testBackendNameAssignment($db)
    {
        $this->setupDbProvider($db);

        $testName = 'test-name-123123';
        $backend = new DbUserBackend($this->createDbBackendConfig($db, $testName));
        $backend->connect();
        $this->assertSame($testName, $backend->getName());
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testCountUsersMySql($db)
    {
        $this->setupDbProvider($db);
        $testName = 'test-name-123123';
        $backend = new DbUserBackend($this->createDbBackendConfig($db, $testName));
        $backend->connect();

        $this->assertGreaterThan(0, $backend->getUserCount());
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testCountUsersPgSql($db)
    {
        $this->setupDbProvider($db);
        $testName = 'test-name-123123';
        $backend = new DbUserBackend($this->createDbBackendConfig($db, $testName));
        $backend->connect();

        $this->assertGreaterThan(0, $backend->getUserCount());
    }
}
