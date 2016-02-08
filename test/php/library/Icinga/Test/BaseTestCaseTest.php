<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Test;

use Mockery;
use Icinga\Test\BaseTestCase;

class BaseTestCaseTest extends BaseTestCase
{
    protected $emptySqlDumpFile;

    public function tearDown()
    {
        parent::tearDown();

        if ($this->emptySqlDumpFile) {
            unlink($this->emptySqlDumpFile);
        }
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testWhetherMySqlProviderAnnotationSetsUpZendDbAdapter($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Mysql', $resource->getConnection());
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testWhetherMySqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $this->dbAdapterSqlLoadTable($resource);
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testWhetherCreatingTablesWithMySqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getConnection();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    /**
     * @dataProvider mysqlDb
     * @depends testWhetherCreatingTablesWithMySqlAdapterWorks
     */
    public function testWhetherSetupDbProviderCleansUpMySqlAdapter($resource)
    {
        $this->setupDbProvider($resource);

        $tables = $resource->getConnection()->listTables();
        $this->assertCount(0, $tables);
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testWhetherPgSqlProviderAnnotationSetsUpZendDbAdapter($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Pgsql', $resource->getConnection());
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testWhetherPgSqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $this->dbAdapterSqlLoadTable($resource);
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testWhetherCreatingTablesWithPgSqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getConnection();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    /**
     * @dataProvider pgsqlDb
     * @depends testWhetherCreatingTablesWithPgSqlAdapterWorks
     */
    public function testWhetherSetupDbProviderCleansUpPgSqlAdapter($resource)
    {
        $this->setupDbProvider($resource);

        $tables = $resource->getConnection()->listTables();
        $this->assertCount(0, $tables);
    }

    /**
     * @dataProvider oracleDb
     */
    public function testWhetherOciProviderAnnotationSetsUpZendDbAdapter($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Oci', $resource->getConnection());
    }

    /**
     * @dataProvider oracleDb
     */
    public function testWhetherOciAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $this->dbAdapterSqlLoadTable($resource);
    }

    /**
     * @dataProvider oracleDb
     */
    public function testWhetherCreatingTablesWithOciAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getConnection();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    /**
     * @dataProvider oracleDb
     * @depends testWhetherCreatingTablesWithOciAdapterWorks
     */
    public function testWhetherSetupDbProviderCleansUpOciAdapter($resource)
    {
        $this->setupDbProvider($resource);

        $tables = $resource->getConnection()->listTables();
        $this->assertCount(0, $tables);
    }

    /**
     * @expectedException   RuntimeException
     */
    public function testWhetherLoadSqlThrowsErrorWhenFileMissing()
    {
        $this->loadSql(Mockery::mock('Icinga\Data\Db\DbConnection'), 'not_existing');
    }

    /**
     * @expectedException   RuntimeException
     */
    public function testWhetherLoadSqlThrowsErrorWhenFileEmpty()
    {
        $this->emptySqlDumpFile = tempnam(sys_get_temp_dir(), 'icinga2-web-db-test-empty');
        $this->loadSql(Mockery::mock('Icinga\Data\Db\DbConnection'), $this->emptySqlDumpFile);
    }

    protected function dbAdapterSqlLoadTable($resource)
    {
        $sqlContent = array();
        $sqlContent[] = 'CREATE TABLE dummyData(value VARCHAR(50) NOT NULL PRIMARY KEY);';
        for ($i=0; $i<20; $i++) {
            $sqlContent[] = 'INSERT INTO dummyData VALUES(\'' . uniqid(). '\');';
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'icinga2-web-test-load-sql');
        file_put_contents($tempFile, implode(PHP_EOL, $sqlContent));

        $this->loadSql($resource, $tempFile);

        $count = (int) $resource->getConnection()->fetchOne('SELECT COUNT(*) as cntX from dummyData;');
        $this->assertSame(20, $count);

        $this->assertTrue(unlink($tempFile));
    }
}
