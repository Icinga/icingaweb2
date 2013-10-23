<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Application;

require_once 'Zend/Db.php';
require_once 'Zend/Db/Adapter/Pdo/Mysql.php';
require_once 'Zend/Config.php';
require_once 'Zend/Log.php';
require_once 'Zend/Config.php';
require_once realpath(__DIR__. '/../../../library/Icinga/Application/ZendDbMock.php');
require_once realpath(__DIR__. '/../../../../../library/Icinga/Application/Logger.php');
require_once realpath(__DIR__. '/../../../../../library/Icinga/Exception/ConfigurationError.php');
require_once realpath(__DIR__. '/../../../../../library/Icinga/Exception/ProgrammingError.php');
require_once realpath(__DIR__. '/../../../../../library/Icinga/Util/ConfigAwareFactory.php');
require_once realpath(__DIR__. '/../../../../../library/Icinga/Application/DbAdapterFactory.php');

use \PDO;
use \Zend_Db;
use \Tests\Icinga\Application\ZendDbMock;
use \Icinga\Application\DbAdapterFactory;

/*
 * Unit test for the class DbAdapterFactory
 */
class DbAdapterFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The resources used for this test
     */
    private $resources;

    /**
     * Set up the test fixture
     */
    public function setUp()
    {
        $this->resources = array(
            /*
             * PostgreSQL database
             */
            'resource1'  =>  array(
                'type'   => 'db',
                'db'     => 'pgsql',
                'dbname' => 'resource1',
                'host'   => 'host1',
                'username'  => 'username1',
                'password'  => 'password1',
                'options'   => array(
                    Zend_Db::AUTO_QUOTE_IDENTIFIERS => false,
                    Zend_Db::CASE_FOLDING           => Zend_Db::CASE_LOWER,
                    Zend_Db::FETCH_MODE             => Zend_Db::FETCH_OBJ
                ),
                'driver_options' => array(
                    PDO::ATTR_TIMEOUT => 2,
                    PDO::ATTR_CASE    => PDO::CASE_LOWER
                ),
                'port' => 5432
            ),
            /*
             * MySQL database
             */
            'resource2'  =>  array(
                'type'   => 'db',
                'db'     => 'mysql',
                'dbname' => 'resource2',
                'host'   => 'host2',
                'username'  => 'username2',
                'password'  => 'password2',
                'options'   => array(
                    Zend_Db::AUTO_QUOTE_IDENTIFIERS => false,
                    Zend_Db::CASE_FOLDING           => Zend_Db::CASE_LOWER,
                    Zend_Db::FETCH_MODE             => Zend_Db::FETCH_OBJ
                ),
                'driver_options' => array(
                    PDO::ATTR_TIMEOUT               => 2,
                    PDO::ATTR_CASE                  => PDO::CASE_LOWER,
                    PDO::MYSQL_ATTR_INIT_COMMAND    =>
                        'SET SESSION SQL_MODE=\'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,'
                        . 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,ANSI_QUOTES,PIPES_AS_CONCAT,'
                        . 'NO_ENGINE_SUBSTITUTION\';'
                ),
                'port' => 3306
            ),
            /*
             * Unsupported database type
             */
            'resource3' => array(
                'type'   => 'db',
                'db'     => 'mssql',
                'dbname' => 'resource3',
                'host'   => 'host3',
                'username'  => 'username3',
                'password'  => 'password3'
            ),
            /*
             * Unsupported resource type
             */
            'resource4' =>  array(
                'type'  => 'ldap',
            ),
        );
        DbAdapterFactory::setConfig(
            $this->resources,
            array(
                'factory'   => '\Tests\Icinga\Application\ZendDbMock'
            )
        );
    }

    public function testGetValidResource()
    {
        DbAdapterFactory::getDbAdapter('resource2');
        $this->assertEquals(
            'Pdo_Mysql',
            ZendDbMock::getAdapter(),
            'The db adapter name must be Pdo_Mysql.'
        );
        $this->assertEquals(
            $this->getOptions($this->resources['resource2']),
            ZendDbMock::getConfig(),
            'The options must match the original config file content'
        );
    }

    public function testResourceExists()
    {
        $this->assertTrue(
            DbAdapterFactory::resourceExists('resource2'),
            'resourceExists() called with an existing resource should return true'
        );

        $this->assertFalse(
            DbAdapterFactory::resourceExists('not existing'),
            'resourceExists() called with an existing resource should return false'
        );

        $this->assertFalse(
            DbAdapterFactory::resourceExists('resource4'),
            'resourceExists() called with an incompatible resource should return false'
        );
    }

    public function testGetResources()
    {
        $withoutIncompatible = array_merge(array(), $this->resources);
        unset($withoutIncompatible['resource4']);
        $this->assertEquals(
            $withoutIncompatible,
            DbAdapterFactory::getResources(),
            'getResources should return an array of all existing resources that are compatible'
        );
    }

    /**
     * Test if an exception is thrown, when an invalid database is used.
     *
     * @expectedException Icinga\Exception\ConfigurationError
     */
    public function testGetInvalidDatabase()
    {
        DbAdapterFactory::getDbAdapter('resource3');
    }

    /**
     * Test if an exception is thrown, when an invalid type is used.
     *
     * @expectedException Icinga\Exception\ConfigurationError
     */
    public function testGetInvalidType()
    {
        DbAdapterFactory::getDbAdapter('resource4');
    }

    /**
     * Prepare the options object for assertions
     *
     * @param   Zend_Config     $config     The configuration to prepare
     *
     * @return  array                       The prepared options object
     */
    private function getOptions($config)
    {
        $options = array_merge(array(), $config);
        unset($options['type']);
        unset($options['db']);
        return $options;
    }
}
