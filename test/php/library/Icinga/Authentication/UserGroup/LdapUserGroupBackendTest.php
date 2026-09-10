<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Authentication\UserGroup;

use Icinga\Application\Logger;
use Icinga\Authentication\UserGroup\LdapUserGroupBackend;
use Icinga\Data\ConfigObject;
use Icinga\Protocol\Ldap\LdapConnection;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Mockery;
use ReflectionProperty;

class LdapUserGroupBackendTest extends BaseTestCase
{
    protected ?string $logFile = null;

    protected ?Logger $previousLogger = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->previousLogger = Logger::getInstance();
        $logFile = tempnam(sys_get_temp_dir(), 'log');
        $this->assertNotFalse($logFile, 'Could not create a temporary log file');
        $this->logFile = $logFile;
        Logger::create(new ConfigObject(['log' => 'file', 'file' => $this->logFile, 'level' => 'WARNING']));
    }

    public function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            try {
                (new ReflectionProperty(Logger::class, 'instance'))->setValue(null, $this->previousLogger);
            } finally {
                if ($this->logFile !== null) {
                    unlink($this->logFile);
                }
            }
        }
    }

    public function testGetMembershipsRejectsUsernameWithAsteriskWithoutQuerying(): void
    {
        $backend = new LdapUserGroupBackend(Mockery::mock(LdapConnection::class));
        $user = new User("foo*\r\n2026-01-01T00:00:00+00:00 - ERROR - forged");

        $this->assertSame([], $backend->getMemberships($user));

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        $this->assertCount(1, $lines, 'Rejected username must not be able to inject log lines');
        $this->assertStringEndsWith(
            ' - WARNING - The LDAP username "foo*\r\n2026-01-01T00:00:00+00:00 - ERROR - forged"'
            . ' must not contain any forbidden characters.',
            $lines[0]
        );
    }
}
