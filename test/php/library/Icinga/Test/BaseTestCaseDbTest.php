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

namespace Tests\Icinga\Test;

require_once 'Zend/Db/Adapter/Pdo/Mysql.php';
require_once 'Zend/Db/Adapter/Pdo/Pgsql.php';
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');

use \PDO;
use \RuntimeException;
use Icinga\Test\BaseTestCase;

class BaseTestCaseDbTest extends BaseTestCase
{
    private $emptySqlDumpFile;

    protected function tearDown()
    {
        if ($this->emptySqlDumpFile) {
            unlink($this->emptySqlDumpFile);
        }
    }

    public function testExistingTestDirectories()
    {
        $this->assertFileExists(self::$appDir);
        $this->assertFileExists(self::$libDir);
        $this->assertFileExists(self::$etcDir);
        $this->assertFileExists(self::$testDir);
        $this->assertFileExists(self::$moduleDir);
        // $this->assertFileExists(self::$shareDir);
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testMySqlProviderAnnotation($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Mysql', $resource);
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testMySqlCreateTablePart1($resource)
    {
        $this->setupDbProvider($resource);
        /** @var \Zend_Db_Adapter_Pdo_Abstract $resource **/
        $resource->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $resource->listTables();
        $this->assertCount(1, $tables);
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testMySqlCreateTablePart2($resource)
    {
        $this->setupDbProvider($resource);
        $tables = $resource->listTables();
        $this->assertCount(0, $tables);
    }

    private function dbAdapterSqlLoadTable($resource)
    {
        /** @var $resource \Zend_Db_Adapter_Pdo_Abstract **/
        $this->setupDbProvider($resource);

        $sqlContent = array();
        $sqlContent[] = 'CREATE TABLE dummyData(value VARCHAR(50) NOT NULL PRIMARY KEY);';
        for ($i=0; $i<20; $i++) {
            $sqlContent[] = 'INSERT INTO dummyData VALUES(\'' . uniqid(). '\');';
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'icinga2-web-test-load-sql');
        file_put_contents($tempFile, implode(chr(10), $sqlContent));

        $this->loadSql($resource, $tempFile);

        $count = (int)$resource->fetchOne('SELECT COUNT(*) as cntX from dummyData;');
        $this->assertSame(20, $count);

        $this->assertTrue(unlink($tempFile));
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testMySqlLoadTable($resource)
    {
        $this->dbAdapterSqlLoadTable($resource);
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testPgSqlProviderAnnotation($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Pgsql', $resource);
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testPgSqlCreateTablePart1($resource)
    {
        $this->setupDbProvider($resource);
        /** @var \Zend_Db_Adapter_Pdo_Abstract $resource **/
        $resource->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $resource->listTables();
        $this->assertCount(1, $tables);
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testPgSqlCreateTablePart2($resource)
    {
        $this->setupDbProvider($resource);
        $tables = $resource->listTables();
        $this->assertCount(0, $tables);
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testPgSqlLoadTable($resource)
    {
        $this->dbAdapterSqlLoadTable($resource);
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testNotExistSqlDumpFile($resource)
    {
        $this->setupDbProvider($resource);

        $this->setExpectedException(
            'RuntimeException',
            'Sql file not found: /does/not/exist1238837 (test=testNotExistSqlDumpFile with data set #0)'
        );

        $this->loadSql($resource, '/does/not/exist1238837');
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testDumpFileIsEmpty($resource)
    {
        $this->setupDbProvider($resource);
        $this->emptySqlDumpFile = tempnam(sys_get_temp_dir(), 'icinga2-web-db-test-empty');
        $this->assertFileExists($this->emptySqlDumpFile);

        $expectedMessage = 'Sql file is empty: '
            . $this->emptySqlDumpFile
            . ' (test=testDumpFileIsEmpty with data set #0)';

        $this->setExpectedException(
            'RuntimeException',
            $expectedMessage
        );

        $this->loadSql($resource, $this->emptySqlDumpFile);

    }
}
