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

require_once('Zend/Config.php');
require_once('Zend/Config/Ini.php');
require_once('Zend/Db/Adapter/Abstract.php');
require_once('Zend/Db.php');
require_once('Zend/Log.php');
require_once('../../library/Icinga/Util/ConfigAwareFactory.php');
require_once('../../library/Icinga/Authentication/UserBackend.php');
require_once('../../library/Icinga/Protocol/Ldap/Exception.php');
require_once('../../library/Icinga/Application/DbAdapterFactory.php');
require_once('../../library/Icinga/Application/Config.php');
require_once('../../library/Icinga/Authentication/Credentials.php');
require_once('../../library/Icinga/Authentication/Backend/DbUserBackend.php');
require_once('../../library/Icinga/User.php');
require_once('../../library/Icinga/Application/Logger.php');

use Zend_Config;
use Zend_Db_Adapter_Abstract;
use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Application\DbAdapterFactory;
use Icinga\Util\Crypto;
use Icinga\Authentication\Credentials;
use Icinga\User;
use \Icinga\Application\Config;

/**
 * Test Class fpr DbUserBackend
 */
class DbUserBackendTest  extends \PHPUnit_Framework_TestCase {

    /*
     * Mapping of columns
     */
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
     * The database that is used to store the authentication data
     *
     * @var string
     */
    private $testDatabase = 'icinga_unittest';

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
     * Create a preset configuration that can be used to access the database
     * with the icinga_unittest account.
     *
     * @param String $dbType    The database type as a string, like 'mysql' or 'pgsql'.
     *
     * @return Zend_Config      The created resource configuration
     */
    private function getResourceConfig($dbType)
    {
        return new Zend_Config(
            array(
                'type'     => 'db',
                'db'       => $dbType,
                'host'     => 'localhost',
                'username' => 'icinga_unittest',
                'password' => 'icinga_unittest',
                'dbname'   => $this->testDatabase,
                'table'    => $this->testTable
            )
        );
    }

    /**
     * Create a backend with the given database type
     *
     * @param   String $dbType      The database type as a string, like 'mysql' or 'pgsql'.
     *
     * @return  DbUserBackend|null
     */
    private function createBackend($dbType)
    {
        try {
            $db = $this->createDb($this->getResourceConfig($dbType));
            $this->setUpDb($db,$dbType);
            return new DbUserBackend($db);
        } catch(\Exception $e) {
            echo 'CREATE_BACKEND_ERROR:'.$e->getMessage();
            return null;
        }
    }

    /**
     * Create the db adapter
     *
     * @param $config                       The configuration to use
     *
     * @return Zend_Db_Adapter_Abstract     The created adabter
     */
    private function createDb($config)
    {
        return DbAdapterFactory::createDbAdapterFromConfig($config);
    }

    /**
     * Create the backends and fill it with sample-data
     */
    protected function setUp()
    {
        DbAdapterFactory::resetConfig();
        $this->users = Array(
            0 => Array(
                self::USER_NAME_COLUMN => 'user1',
                self::PASSWORD_COLUMN  => 'secret1',
                self::SALT_COLUMN      => '8a7487a539c5d1d6766639d04d1ed1e6',
                self::ACTIVE_COLUMN    => 1
            ),
            1 => Array(
                self::USER_NAME_COLUMN => 'user2',
                self::PASSWORD_COLUMN  => 'secret2',
                self::SALT_COLUMN      => '04b5521ddd761b5a5b633be83faa494d',
                self::ACTIVE_COLUMN    => 1
            ),
            2 => Array(
                self::USER_NAME_COLUMN => 'user3',
                self::PASSWORD_COLUMN  => 'secret3',
                self::SALT_COLUMN      => '08bb94ba3120338ae56db80ef551d324',
                self::ACTIVE_COLUMN    => 0
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
        if (!empty($this->pgsql)) {
            $this->runBackendAuthentication($this->pgsql);
            $this->runBackendUsername($this->pgsql);
        }
        else {
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
            $this->markTestSkipped();
        }
    }

    /**
     * Try to drop all databases that may eventually be present
     */
    public function tearDown()
    {
        try{
            $db = $this->createDb($this->getResourceConfig('mysql'));
            $this->tearDownDb($db);
        } catch(\Exception $e) { }
        try {
            $db = $this->createDb($this->getResourceConfig('pgsql'));
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
     * @param $db   Zend_Db_Adapter_Abstract    The database to set up
     * @param $type String                      The database type as a string: 'mysql'|'pgsql'
     */
    private function setUpDb($db,$type)
    {
        try {
            $this->tearDownDb($db);
        } catch (\Exception $e) {}

        $setupScript = file_get_contents('../../etc/schema/accounts.' . $type . '.sql');
        $db->exec($setupScript);

        for ($i = 0; $i < count($this->users); $i++) {
            $usr = $this->users[$i];
            $data = Array(
                self::USER_NAME_COLUMN => $usr[self::USER_NAME_COLUMN],
                self::PASSWORD_COLUMN  => hash_hmac('sha256',
                    $usr[self::SALT_COLUMN],
                    $usr[self::PASSWORD_COLUMN]
                ),
                self::ACTIVE_COLUMN    => $usr[self::ACTIVE_COLUMN],
                self::SALT_COLUMN      => $usr[self::SALT_COLUMN]
            );
            $db->insert($this->testTable,$data);
        }
    }


    /**
     * Run the hasUsername test against an instance of DbUserBackend
     *
     * @param $backend      The backend that will be tested.
     */
    private function runBackendUsername($backend)
    {
        // Known user
        $this->assertTrue($backend->hasUsername(
            new Credentials(
                $this->users[0][self::USER_NAME_COLUMN],
                $this->users[0][self::PASSWORD_COLUMN])
        ), 'Assert that the user is known by the backend');

        // Unknown user
        $this->assertFalse($backend->hasUsername(
            new Credentials(
                'unknown user',
                'secret')
        ), 'Assert that the user is not known by the backend');

        // Inactive user
        $this->assertFalse($backend->hasUsername(
            new Credentials(
                $this->users[2][self::USER_NAME_COLUMN],
                $this->users[2][self::PASSWORD_COLUMN])
        ), 'Assert that the user is inactive and therefore not known by the backend');
    }

    /**
     * Run the authentication test against an instance of DbUserBackend
     *
     * @param $backend      The backend that will be tested.
     */
    private function runBackendAuthentication($backend)
    {
        // Known user
        $this->assertNotNull($backend->authenticate(
            new Credentials(
                $this->users[0][self::USER_NAME_COLUMN],
                $this->users[0][self::PASSWORD_COLUMN])
        ), 'Assert that an existing, active user with the right credentials can authenticate.');

        // Wrong password
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    $this->users[1][self::USER_NAME_COLUMN],
                    'wrongpassword')
            ), 'Assert that an existing user with an invalid password cannot authenticate'
        );

        // Nonexisting user
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    'nonexisting user',
                    $this->users[1][self::PASSWORD_COLUMN])
            ), 'Assert that a non-existing user cannot authenticate.'
        );

        // Inactive user
        $this->assertNull(
            $backend->authenticate(
                new Credentials(
                    $this->users[2][self::USER_NAME_COLUMN],
                    $this->users[2][self::PASSWORD_COLUMN])
        ), 'Assert that an inactive user cannot authenticate.');
    }
}