<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Authentication\User;

use Icinga\Authentication\User\LdapUserBackend;
use Icinga\Exception\AuthenticationException;
use Icinga\Protocol\Ldap\LdapConnection;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Mockery;

class LdapUserBackendTest extends BaseTestCase
{
    public function testAuthenticateRejectsUsernameWithAsteriskWithoutQuerying(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The LDAP username must not contain any forbidden characters.');

        (new LdapUserBackend(Mockery::mock(LdapConnection::class)))->authenticate(new User('foo*'), 'secret');
    }
}
