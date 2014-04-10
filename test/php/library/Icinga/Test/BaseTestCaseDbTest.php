<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Test;

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

    /**
     * @dataProvider mysqlDb
     */
    public function testMySqlProviderAnnotation($resource)
    {
        $this->setupDbProvider($resource);
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Mysql', $resource->getConnection());
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testMySqlCreateTablePart1($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getConnection();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    /**
     * @dataProvider mysqlDb
     */
    public function testMySqlCreateTablePart2($resource)
    {
        $this->setupDbProvider($resource);
        $tables = $resource->getConnection()->listTables();
        $this->assertCount(0, $tables);
    }

    private function dbAdapterSqlLoadTable($resource)
    {
        $this->setupDbProvider($resource);

        $sqlContent = array();
        $sqlContent[] = 'CREATE TABLE dummyData(value VARCHAR(50) NOT NULL PRIMARY KEY);';
        for ($i=0; $i<20; $i++) {
            $sqlContent[] = 'INSERT INTO dummyData VALUES(\'' . uniqid(). '\');';
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'icinga2-web-test-load-sql');
        file_put_contents($tempFile, implode(chr(10), $sqlContent));

        $this->loadSql($resource, $tempFile);

        $count = (int) $resource->getConnection()->fetchOne('SELECT COUNT(*) as cntX from dummyData;');
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
        $this->assertInstanceOf('Zend_Db_Adapter_Pdo_Pgsql', $resource->getConnection());
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testPgSqlCreateTablePart1($resource)
    {
        $this->setupDbProvider($resource);
        $adapter = $resource->getConnection();
        $adapter->exec('CREATE TABLE test(uid INT NOT NULL PRIMARY KEY);');

        $tables = $adapter->listTables();
        $this->assertCount(1, $tables);
    }

    /**
     * @dataProvider pgsqlDb
     */
    public function testPgSqlCreateTablePart2($resource)
    {
        $this->setupDbProvider($resource);
        $tables = $resource->getConnection()->listTables();
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
