<?php

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

//use Icinga\Protocol\Ldap\Exception;
//use Zend_Config_Ini;

require_once('Zend/Config/Ini.php');
require_once('Zend/Db.php');
require_once('../../library/Icinga/Authentication/UserBackend.php');
require_once('../../library/Icinga/Protocol/Ldap/Exception.php');
require_once('../../library/Icinga/Application/Config.php');
require_once('../../library/Icinga/Authentication/Credentials.php');
require_once('../../library/Icinga/Authentication/Backend/DbUserBackend.php');
require_once('../../library/Icinga/User.php');

use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Util\Crypto;
use Icinga\Authentication\Credentials;
use Icinga\User;
use \Icinga\Application\Config;

/**
 *
 * Test Class fpr DbUserBackend
 * Created Wed, 17 Jul 2013 11:52:34 +0000
 *
 */
class DbUserBackendTest  extends \PHPUnit_Framework_TestCase {

    /**
     * The table that is used to store the authentication data
     *
     * @var string
     */
    private $testTable = 'account';

    /**
     * The database that is used to store the authentication data
     *
     * @var string
     */
    private $testDatabase = 'icinga_unittest';

    /**
     * Mapping of columns
     *
     * @var string
     */
    private $USER_NAME_COLUMN   = 'user_name',
            $FIRST_NAME_COLUMN  = 'first_name',
            $LAST_NAME_COLUMN   = 'last_name',
            $LAST_LOGIN_COLUMN  = 'last_login',
            $SALT_COLUMN        = 'salt',
            $PASSWORD_COLUMN    = 'password',
            $ACTIVE_COLUMN      = 'active',
            $DOMAIN_COLUMN      = 'domain',
            $EMAIL_COLUMN       = 'email';

    /**
     * Example users
     *
     * @var array
     */
    private $users;

    /**
     * The DbUserBackend configured to use MySQL
     *
     * @var DbUserBackend
     */
    private $mysql;


    /**
     * The DbUserBackend configured to use PostgreSQL
     *
     * @var DbUserBackend
     */
    private $pgsql;

    /**
     * Contains the PDO names used for the different SQL databases.
     *
     * @var array
     */
    private $dbTypeMap = Array(
        'mysql' => 'PDO_MYSQL',
        'pgsql' => 'PDO_PGSQL'
    );

    /**
     * Create a preset-configuration that can be used to access the database
     * with the icinga_unittest account.
     *
     * @return \stdClass
     */
    private function getBackendConfig()
    {
        $config = new \stdClass();
        $config->host = '127.0.0.1';
        $config->user = 'icinga_unittest';
        $config->password= 'icinga_unittest';
        $config->table = $this->testTable;
        $config->db = $this->testDatabase;
        return $config;
    }

    /**
     * Create a backend with the given database type
     *
     * @param $dbType The database type as a string, like 'mysql' or 'pgsql'.
     *
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
            echo 'CREATE_BACKEND_ERROR:'.$e->getMessage();
            return null;
        }
    }

    /**
     * Create the backends and fill it with sample-data
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
        $this->mysql = $this->createBackend('mysql');
        $this->pgsql = $this->createBackend('pgsql');
    }

    /**
     * Test the authentication functions of the DbUserBackend using PostgreSQL as backend.
     */
    public function testCorrectUserLoginForPgsql()
    {
        if(!empty($this->pgsql)){
            $this->runBackendAuthentication($this->pgsql);
            $this->runBackendUsername($this->pgsql);
        }
        else{
            echo '\nSKIPPING PGSQL TEST...\n';
            $this->markTestSkipped();
        }
    }

    /**
     * Test the authentication functions of the DbUserBackend using MySQL as backend.
     */
    public function testCorrectUserLoginForMySQL()
    {
        if(!empty($this->mysql)){
            $this->runBackendAuthentication($this->mysql);
            $this->runBackendUsername($this->mysql);
        }
        else{
            echo '\nSKIPPING MYSQL TEST...\n';
            $this->markTestSkipped();
        }
    }

    /**
     * Create a database with the given config and type
     *
     * @param $dbtype The database type as a string, like 'mysql' or 'pgsql'.
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
                'dbname'    => 'icinga_unittest'
            ));
    }

    /**
     * Try to drop all databases that may eventually be present
     */
    public function tearDown()
    {
        try{
            $db = $this->createDb('mysql',$this->getBackendConfig());
            $this->tearDownDb($db);
        } catch(\Exception $e) { }
        try {
            $db = $this->createDb('pgsql',$this->getBackendConfig());
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
        try {
            $this->tearDownDb($db);
        } catch (\Exception $e) {
            // if no database exists, an exception will be thrown
        }
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
                $this->PASSWORD_COLUMN  => hash_hmac('sha256',
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
        ),'Assert that the user is known by the backend');

        // Unknown user
        $this->assertFalse($backend->hasUsername(
            new Credentials(
                'unknown user',
                'secret')
        ),'Assert that the user is not known by the backend');

        // Inactive user
        $this->assertFalse($backend->hasUsername(
            new Credentials(
                $this->users[2][$this->USER_NAME_COLUMN],
                $this->users[2][$this->PASSWORD_COLUMN])
        ),'Assert that the user is inactive and therefore not known by the backend');
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
        ),'Assert that an existing, active user with the right credentials can authenticate.');

        // Wrong password
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    $this->users[1][$this->USER_NAME_COLUMN],
                    'wrongpassword')
            ),'Assert that an existing user with an invalid password cannot authenticate'
        );

        // Nonexisting user
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    'nonexisting user',
                    $this->users[1][$this->PASSWORD_COLUMN])
            ),'Assert that a non-existing user cannot authenticate.'
        );

        // Inactive user
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    $this->users[2][$this->USER_NAME_COLUMN],
                    $this->users[2][$this->PASSWORD_COLUMN])
        ),'Assert that an inactive user cannot authenticate.');
    }
}