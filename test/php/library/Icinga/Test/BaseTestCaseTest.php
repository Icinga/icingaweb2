<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Test;

use Mockery;
use Icinga\Test\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

class BaseTestCaseTest extends BaseTestCase
{
    protected $emptySqlDumpFile;

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->emptySqlDumpFile) {
            unlink($this->emptySqlDumpFile);
        }
    }

    #[DataProvider('mysqlDb')]
    public function testWhetherMySqlProviderAnnotationSetsUpZendDbAdapter($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Mysql', $resource->getDbAdapter());
    }

    #[DataProvider('mysqlDb')]
    public function testWhetherMySqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $this->dbAdapterSqlLoadTable($resource);
    }

    #[DataProvider('mysqlDb')]
    public function testWhetherCreatingTablesWithMySqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getDbAdapter();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    #[DataProvider('mysqlDb')]
    #[Depends('testWhetherCreatingTablesWithMySqlAdapterWorks')]
    public function testWhetherSetupDbProviderCleansUpMySqlAdapter($resource)
    {
        $this->setupDbProvider($resource);

        $tables = $resource->getDbAdapter()->listTables();
        $this->assertCount(0, $tables);
    }

    #[DataProvider('pgsqlDb')]
    public function testWhetherPgSqlProviderAnnotationSetsUpZendDbAdapter($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Pgsql', $resource->getDbAdapter());
    }

    #[DataProvider('pgsqlDb')]
    public function testWhetherPgSqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $this->dbAdapterSqlLoadTable($resource);
    }

    #[DataProvider('pgsqlDb')]
    public function testWhetherCreatingTablesWithPgSqlAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getDbAdapter();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    #[DataProvider('pgsqlDb')]
    #[Depends('testWhetherCreatingTablesWithPgSqlAdapterWorks')]
    public function testWhetherSetupDbProviderCleansUpPgSqlAdapter($resource)
    {
        $this->setupDbProvider($resource);

        $tables = $resource->getDbAdapter()->listTables();
        $this->assertCount(0, $tables);
    }

    #[DataProvider('oracleDb')]
    public function testWhetherOciProviderAnnotationSetsUpZendDbAdapter($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Oci', $resource->getDbAdapter());
    }

    #[DataProvider('oracleDb')]
    public function testWhetherOciAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $this->dbAdapterSqlLoadTable($resource);
    }

    #[DataProvider('oracleDb')]
    public function testWhetherCreatingTablesWithOciAdapterWorks($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getDbAdapter();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    #[DataProvider('oracleDb')]
    #[Depends('testWhetherCreatingTablesWithOciAdapterWorks')]
    public function testWhetherSetupDbProviderCleansUpOciAdapter($resource)
    {
        $this->setupDbProvider($resource);

        $tables = $resource->getDbAdapter()->listTables();
        $this->assertCount(0, $tables);
    }

    public function testWhetherLoadSqlThrowsErrorWhenFileMissing()
    {
        $this->expectException(\RuntimeException::class);

        $this->loadSql(Mockery::mock('Icinga\Data\Db\DbConnection'), 'not_existing');
    }

    public function testWhetherLoadSqlThrowsErrorWhenFileEmpty()
    {
        $this->expectException(\RuntimeException::class);

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

        $count = (int) $resource->getDbAdapter()->fetchOne('SELECT COUNT(*) as cntX from dummyData;');
        $this->assertSame(20, $count);

        $this->assertTrue(unlink($tempFile));
    }
}
