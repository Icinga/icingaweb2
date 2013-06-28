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
require_once("../../library/Icinga/Util/Crypto.php");
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
    private $unknownUsers;

    private $dbTypeMap = Array(
        'mysql' => 'PDO_MYSQL',
        'pgsql' => 'PDO_PGSQL'
    );

    protected function setUp()
    {
        $this->users = Array(
            0 => Array(
                $this->USER_NAME_COLUMN => 'user1',
                $this->PASSWORD_COLUMN  => 'secret1',
                $this->SALT_COLUMN      => '8a7487a539c5d1d6766639d04d1ed1e6',
                $this->ACTIVE_COLUMN    => true
            ),
            1 => Array(
                $this->USER_NAME_COLUMN => 'user2',
                $this->PASSWORD_COLUMN  => 'secret2',
                $this->SALT_COLUMN      => '04b5521ddd761b5a5b633be83faa494d',
                $this->ACTIVE_COLUMN    => true
            ),
            2 => Array(
                $this->USER_NAME_COLUMN => 'user3',
                $this->PASSWORD_COLUMN  => 'secret3',
                $this->SALT_COLUMN      => '08bb94ba3120338ae56db80ef551d324',
                $this->ACTIVE_COLUMN    => false
            )
        );

        // TODO: Fetch config folder from somewhere instead of defining it statically.
        Config::$configDir = "/vagrant/config";
        $config = Config::app('authentication')->users;
        $config->table = $this->testTable;

        $this->db = \Zend_Db::factory($this->dbTypeMap[$config->dbtype],
            array(
                'host'      => $config->host,
                'username'  => $config->user,
                'password'  => $config->password,
                'dbname'    => $config->db
            ));

        if($config->dbtype == 'pgsql'){
            $this->users[0][$this->ACTIVE_COLUMN] = "TRUE";
            $this->users[1][$this->ACTIVE_COLUMN] = "TRUE";
            $this->users[2][$this->ACTIVE_COLUMN] = "FALSE";
        }
        $this->setUpDb($this->db);
        $this->dbUserBackend = new DbUserBackend($config);
    }

    public function tearDown()
    {
        $this->tearDownDb($this->db);
    }

    private function setUpDb($db){

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

        for($i = 0; $i < count($this->users); $i++){
            $usr = $this->users[$i];
            $data = Array(
                $this->USER_NAME_COLUMN => $usr[$this->USER_NAME_COLUMN],
                $this->PASSWORD_COLUMN  => Crypto::hashPassword(
                    $usr[$this->PASSWORD_COLUMN],
                    $usr[$this->SALT_COLUMN]),
                $this->ACTIVE_COLUMN    => $usr[$this->ACTIVE_COLUMN],
                $this->SALT_COLUMN      => $usr[$this->SALT_COLUMN]
            );
            $db->insert($this->testTable,$data);
        }
    }

    private function tearDownDb($db){
        $db->exec('DROP TABLE '.$this->testTable);
    }

    /**
     * Test for DbUserBackend::HasUsername()
     **/
    public function testHasUsername(){

        // Known user
        $this->assertTrue($this->dbUserBackend->hasUsername(
            new Credentials(
                $this->users[0][$this->USER_NAME_COLUMN],
                $this->users[0][$this->PASSWORD_COLUMN])
        ));

        // Unknown user
        $this->assertFalse($this->dbUserBackend->hasUsername(
            new Credentials(
                'unkown user',
                'secret')
        ));

        // Inactive user
        $this->assertFalse($this->dbUserBackend->hasUsername(
            new Credentials(
                $this->users[2][$this->USER_NAME_COLUMN],
                $this->users[2][$this->PASSWORD_COLUMN])
        ));

    }

    /**
     * Test for DbUserBackend::Authenticate()
     *
     **/
    public function testAuthenticate(){
        // Known user
        $this->assertNotNull($this->dbUserBackend->authenticate(
            new Credentials(
                $this->users[0][$this->USER_NAME_COLUMN],
                $this->users[0][$this->PASSWORD_COLUMN])
        ));

        // Wrong password
        $this->assertNull(
            $this->dbUserBackend->authenticate(
                new Credentials(
                    $this->users[1][$this->USER_NAME_COLUMN],
                    'wrongpassword')
            )
        );

        // Nonexistend user
        $this->assertNull(
            $this->dbUserBackend->authenticate(
                new Credentials(
                    'nonexistend user',
                    $this->users[1][$this->PASSWORD_COLUMN])
            )
        );

        // Inactive user
        $this->assertNull($this->dbUserBackend->authenticate(
            new Credentials(
                $this->users[2][$this->USER_NAME_COLUMN],
                $this->users[2][$this->PASSWORD_COLUMN])
        ));
    }
}