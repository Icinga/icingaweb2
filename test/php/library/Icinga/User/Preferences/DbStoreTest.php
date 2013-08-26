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

namespace Tests\Icinga\User\Preferences;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__. '/../../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once 'Zend/Db.php';
require_once 'Zend/Db/Adapter/Abstract.php';
require_once BaseTestCase::$libDir . '/Exception/ConfigurationError.php';
require_once BaseTestCase::$libDir . '/User.php';
require_once BaseTestCase::$libDir . '/User/Preferences.php';
require_once BaseTestCase::$libDir . '/User/Preferences/LoadInterface.php';
require_once BaseTestCase::$libDir . '/User/Preferences/FlushObserverInterface.php';
require_once BaseTestCase::$libDir . '/User/Preferences/DbStore.php';
// @codingStandardsIgnoreEnd

use \Zend_Db_Adapter_PDO_Abstract;
use Icinga\User;
use Icinga\User\Preferences\DbStore;
use Icinga\User\Preferences;

class DbStoreTest extends BaseTestCase
{

    private function createDbStore(Zend_Db_Adapter_PDO_Abstract $db)
    {
        $user = new User('jdoe');

        $store = new DbStore();
        $store->setDbAdapter($db);
        $store->setUser($user);

        return $store;
    }

    /**
     * @dataProvider    mysqlDb
     * @param           Zend_Db_Adapter_PDO_Abstract $mysqlDb
     */
    public function testCreateUpdateDeletePreferenceValuesMySQL($mysqlDb)
    {
        $this->setupDbProvider($mysqlDb);

        $this->loadSql(
            $mysqlDb,
            $sqlDumpFile = BaseTestCase::$etcDir . '/schema/preferences.mysql.sql'
        );

        $store = $this->createDbStore($mysqlDb);

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
    }

    /**
     * @dataProvider    pgsqlDb
     * @param           Zend_Db_Adapter_PDO_Abstract $pgsqlDb
     */
    public function testCreateUpdateDeletePreferenceValuesPgSQL($pgsqlDb)
    {
        $this->setupDbProvider($pgsqlDb);

        $this->loadSql(
            $pgsqlDb,
            $sqlDumpFile = BaseTestCase::$etcDir . '/schema/preferences.pgsql.sql'
        );

        $store = $this->createDbStore($pgsqlDb);

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
    }
}
