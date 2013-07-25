<?php

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

//use Icinga\Protocol\Ldap\Exception;
//use Zend_Config_Ini;

require_once("Zend/Config/Ini.php");
require_once("Zend/Db.php");
require_once("../../library/Icinga/Authentication/UserBackend.php");
require_once("../../library/Icinga/Protocol/Ldap/Exception.php");
require_once("../../library/Icinga/Application/Config.php");
require_once("../../library/Icinga/Authentication/Credentials.php");
require_once("../../library/Icinga/Authentication/Backend/DbUserBackend.php");
require_once("../../library/Icinga/Authentication/User.php");

use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Util\Crypto;
use Icinga\Authentication\Credentials;
use Icinga\Authentication\User;
use Icinga\Application\Config;

/**
 * Test Class fpr DbUserBackend
 * Created Wed, 17 Jul 2013 11:52:34 +0000
 *
 * @package Tests\Icinga\Authentication
 */
class DbUserBackendTest  extends \PHPUnit_Framework_TestCase {

    private $dbUserBackend;
    private $db;
    private $testTable = "icinga_users_test";
    private $testDatabase = "icinga_unittest";

    /*
     * Must be identical with the column names defined in DbUserBackend
     */
    private $USER_NAME_COLUMN   = "user_name",
            $FIRST_NAME_COLUMN  = "first_name",
            $LAST_NAME_COLUMN   = "last_name",
            $LAST_LOGIN_COLUMN  = "last_login",
            $SALT_COLUMN        = "salt",
            $PASSWORD_COLUMN    = "password",
            $ACTIVE_COLUMN      = "active",
            $DOMAIN_COLUMN      = "domain",
            $EMAIL_COLUMN       = "email";

    private $users;
    private $mysql;
    private $pgsql;

    private $dbTypeMap = Array(
        'mysql' => 'PDO_MYSQL',
        'pgsql' => 'PDO_PGSQL'
    );

    /**
     * Create a preset-configuration that can be used to access the database
     *
     * with the icinga_unittest account.
     * @return \stdClass
     */
    private function getBackendConfig()
    {
        $config = new \stdClass();
        $config->host = "127.0.0.1";
        $config->user = "icinga_unittest";
        $config->password= "icinga_unittest";
        $config->table = $this->testTable;
        $config->db = $this->testDatabase;
        return $config;
    }

    /**
     * Create a backend with the given database type
     *
     * @param $dbType The database type as a string, like "mysql" or "pgsql".
     * @return DbUserBackend|null
     */
    private function createBackend($dbType)
    {
        try {
            $config = $this->getBackendConfig();
            $config->dbtype = $dbType;
            $db = $this->createDb($dbType,$config);
            $this->setUpDb($db);
            return new DbUserBackend($config);
        } catch(\Exception $e) {
            echo "CREATE_BACKEND_ERROR:".$e->getMessage();
            return null;
        }
    }

    /**
     * Create the backends and fill it with sample-data.
     */
    protected function setUp()
    {
        $this->users = Array(
            0 => Array(
                $this->USER_NAME_COLUMN => 'user1',
                $this->PASSWORD_COLUMN  => 'secret1',
                $this->SALT_COLUMN      => '8a7487a539c5d1d6766639d04d1ed1e6',
                $this->ACTIVE_COLUMN    => 1
            ),
            1 => Array(
                $this->USER_NAME_COLUMN => 'user2',
                $this->PASSWORD_COLUMN  => 'secret2',
                $this->SALT_COLUMN      => '04b5521ddd761b5a5b633be83faa494d',
                $this->ACTIVE_COLUMN    => 1
            ),
            2 => Array(
                $this->USER_NAME_COLUMN => 'user3',
                $this->PASSWORD_COLUMN  => 'secret3',
                $this->SALT_COLUMN      => '08bb94ba3120338ae56db80ef551d324',
                $this->ACTIVE_COLUMN    => 0
            )
        );
        $this->mysql = $this->createBackend("mysql");
        $this->pgsql = $this->createBackend("pgsql");
    }

    /**
     * Test the PostgreSQL backend.
     */
    public function testPgsql()
    {
        if(!empty($this->pgsql)){
            $this->runBackendAuthentication($this->pgsql);
            $this->runBackendUsername($this->pgsql);
        }
        else{
            echo "\nSKIPPING PGSQL TEST...\n";
            $this->markTestSkipped();
        }
    }

    /**
     * Test the MySQL-Backend.
     */
    public function testMySQL()
    {
        if(!empty($this->mysql)){
            $this->runBackendAuthentication($this->mysql);
            $this->runBackendUsername($this->mysql);
        }
        else{
            echo "\nSKIPPING MYSQL TEST...\n";
            $this->markTestSkipped();
        }
    }

    /**
     * Create a database with the given config and type
     *
     * @param $dbtype The database type as a string, like "mysql" or "pgsql".
     * @param $config The configuration-object.
     * @return mixed
     */
    private function createDb($dbtype,$config)
    {
        return \Zend_Db::factory($this->dbTypeMap[$dbtype],
            array(
                'host'      => $config->host,
                'username'  => $config->user,
                'password'  => $config->password,
                "dbname"    => "icinga_unittest"
            ));
    }

    /**
     * Try to drop all databases that may eventually be present
     */
    public function tearDown()
    {
        try{
            $db = $this->createDb("mysql",$this->getBackendConfig());
            $this->tearDownDb($db);
        } catch(\Exception $e) { }
        try {
            $db = $this->createDb("pgsql",$this->getBackendConfig());
            $this->tearDownDb($db);
        } catch(\Exception $e) { }
    }

    /**
     * Drop the test database in the given db
     *
     * @param $db
     */
    private function tearDownDb($db)
    {
        $db->exec('DROP TABLE '.$this->testTable);
    }

    /**
     * Fill the given database with the sample-data provided in users
     *
     * @param $db
     */
    private function setUpDb($db)
    {
        $db->exec('CREATE TABLE '.$this->testTable.' (
                  '.$this->USER_NAME_COLUMN.' varchar(255) NOT NULL,
                  '.$this->FIRST_NAME_COLUMN.' varchar(255),
                  '.$this->LAST_NAME_COLUMN.' varchar(255),
                  '.$this->LAST_LOGIN_COLUMN.' timestamp,
                  '.$this->SALT_COLUMN.' varchar(255),
                  '.$this->DOMAIN_COLUMN.' varchar(255),
                  '.$this->EMAIL_COLUMN.' varchar(255),
                  '.$this->PASSWORD_COLUMN.' varchar(255) NOT NULL,
                  '.$this->ACTIVE_COLUMN.' BOOL,
                  PRIMARY KEY ('.$this->USER_NAME_COLUMN.')
            )');
        for ($i = 0; $i < count($this->users); $i++) {
            $usr = $this->users[$i];
            $data = Array(
                $this->USER_NAME_COLUMN => $usr[$this->USER_NAME_COLUMN],
                $this->PASSWORD_COLUMN  => hash_hmac("sha256",
                    $usr[$this->SALT_COLUMN],
                    $usr[$this->PASSWORD_COLUMN]
                    ),
                $this->ACTIVE_COLUMN    => $usr[$this->ACTIVE_COLUMN],
                $this->SALT_COLUMN      => $usr[$this->SALT_COLUMN]
            );
            $db->insert($this->testTable,$data);
        }
    }


    /**
     * Run the hasUsername test against an instance of DbUserBackend
     *
     * @param $backend The backend that will be tested.
     */
    private function runBackendUsername($backend)
    {
        // Known user
        $this->assertTrue($backend->hasUsername(
            new Credentials(
                $this->users[0][$this->USER_NAME_COLUMN],
                $this->users[0][$this->PASSWORD_COLUMN])
        ));

        // Unknown user
        $this->assertFalse($backend->hasUsername(
            new Credentials(
                'unkown user',
                'secret')
        ));

        // Inactive user
        $this->assertFalse($backend->hasUsername(
            new Credentials(
                $this->users[2][$this->USER_NAME_COLUMN],
                $this->users[2][$this->PASSWORD_COLUMN])
        ));
    }

    /**
     * Run the authentication test against an instance of DbUserBackend
     *
     * @param $backend The backend that will be tested.
     */
    private function runBackendAuthentication($backend)
    {
        // Known user
        $this->assertNotNull($backend->authenticate(
            new Credentials(
                $this->users[0][$this->USER_NAME_COLUMN],
                $this->users[0][$this->PASSWORD_COLUMN])
        ));

        // Wrong password
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    $this->users[1][$this->USER_NAME_COLUMN],
                    'wrongpassword')
            )
        );

        // Nonexisting user
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    'nonexisting user',
                    $this->users[1][$this->PASSWORD_COLUMN])
            )
        );

        // Inactive user
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    $this->users[2][$this->USER_NAME_COLUMN],
                    $this->users[2][$this->PASSWORD_COLUMN])
        ));
    }
}