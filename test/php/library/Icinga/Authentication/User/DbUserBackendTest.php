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
use ReflectionProperty;
use Zend_Db_Adapter_Pdo_Mysql;
use Zend_Db_Statement_Interface;

class DbUserBackendTest extends BaseTestCase
{
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
