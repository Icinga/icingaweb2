<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Authentication\UserGroup;

use ArrayIterator;
use Icinga\Authentication\UserGroup\DbUserGroupBackend;
use Icinga\Data\ConfigObject;
use Icinga\Data\Db\DbConnection;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Mockery;
use ReflectionProperty;
use Zend_Db_Adapter_Pdo_Mysql;

class DbUserGroupBackendTest extends BaseTestCase
{
    public function testGetMembershipsMatchesAsteriskInUsernameLiterally(): void
    {
        $queries = [];
        $adapter = Mockery::mock(
            Zend_Db_Adapter_Pdo_Mysql::class . '[quote,query]',
            [['dbname' => 'icingaweb', 'username' => 'icingaweb', 'password' => 'icingaweb']]
        );
        $adapter->shouldReceive('quote')->andReturnUsing(fn ($value) => "'$value'");
        $adapter->shouldReceive('query')->andReturnUsing(function ($select) use (&$queries) {
            $queries[] = (string) $select;

            return new class extends ArrayIterator {
                public function setFetchMode(int $mode): void
                {
                }
            };
        });

        $db = new DbConnection(new ConfigObject(['db' => 'mysql']));
        (new ReflectionProperty(DbConnection::class, 'dbAdapter'))->setValue($db, $adapter);

        $this->assertSame([], (new DbUserGroupBackend($db))->getMemberships(new User('foo*')));

        $membershipQuery = end($queries);
        $this->assertStringContainsString("gm.username COLLATE utf8mb4_general_ci = 'foo*'", $membershipQuery);
        $this->assertStringNotContainsString('LIKE', $membershipQuery);
    }
}
