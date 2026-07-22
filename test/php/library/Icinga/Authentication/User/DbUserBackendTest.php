<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Authentication\User;

use Icinga\Authentication\User\DbUserBackend;
use Icinga\Data\Db\DbConnection;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;

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
}
