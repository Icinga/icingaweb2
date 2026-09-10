<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Authentication\User;

use Icinga\Authentication\User\DbUserBackend;
use Icinga\Data\ConfigObject;
use Icinga\Data\Db\DbConnection;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Zend_Db_Adapter_Pdo_Mysql;
use Zend_Db_Statement_Interface;

class DbUserBackendTest extends BaseTestCase
{
    #[DataProvider('pgsqlDb')]
    public function testAuthenticatesPostgreSqlByteaPasswordHash(DbConnection|string $resource): void
    {
        $this->setupDbProvider($resource);

        $adapter = $resource->getDbAdapter();
        $adapter->exec(
            'CREATE TABLE icingaweb_user ('
            . 'name VARCHAR(254) PRIMARY KEY, '
            . 'active SMALLINT NOT NULL, '
            . 'password_hash BYTEA NOT NULL, '
            . 'ctime TIMESTAMP NULL, '
            . 'mtime TIMESTAMP NULL'
            . ')'
        );

        $password = 'correct horse battery staple';
        $statement = $adapter->prepare(
            'INSERT INTO icingaweb_user (name, active, password_hash) '
            . 'VALUES (:name, :active, :password_hash)'
        );
        $statement->bindValue(':name', 'postgres-user', PDO::PARAM_STR);
        $statement->bindValue(':active', 1, PDO::PARAM_INT);
        $statement->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_LOB);
        $statement->execute();

        $backend = new DbUserBackend($resource);
        $user = new User('postgres-user');

        $this->assertTrue($backend->authenticate($user, $password));
        $this->assertFalse($backend->authenticate($user, 'wrong password'));
    }

    public function testAuthenticateMatchesAsteriskInUsernameLiterally(): void
    {
        $sql = null;
        $adapter = Mockery::mock(
            Zend_Db_Adapter_Pdo_Mysql::class . '[quote,prepare]',
            [['dbname' => 'icingaweb', 'username' => 'icingaweb', 'password' => 'icingaweb']]
        );
        $adapter->shouldReceive('quote')->andReturnUsing(fn ($value) => "'$value'");
        $adapter->shouldReceive('prepare')->andReturnUsing(function ($select) use (&$sql) {
            $sql = (string) $select;

            return Mockery::mock(Zend_Db_Statement_Interface::class)->shouldIgnoreMissing();
        });

        $db = new DbConnection(new ConfigObject(['db' => 'mysql']));
        (new ReflectionProperty(DbConnection::class, 'dbAdapter'))->setValue($db, $adapter);

        $this->assertFalse((new DbUserBackend($db))->authenticate(new User('foo*'), 'secret'));
        $this->assertStringContainsString("BINARY LOWER(name) = 'foo*'", $sql);
        $this->assertStringNotContainsString('LIKE', $sql);
    }
}
