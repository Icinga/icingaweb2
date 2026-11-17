<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use Icinga\Web\Url;
use RuntimeException;

class FailingAuthenticationHook extends AuthenticationHook
{
    public function getLogoutRedirectUrl(User $user): ?Url
    {
        throw new RuntimeException('Session data is missing');
    }
}

class IdentityProviderAuthenticationHook extends AuthenticationHook
{
    public function getLogoutRedirectUrl(User $user): ?Url
    {
        return Url::fromPath('https://idp.example.com/logout');
    }
}

class OtherIdentityProviderAuthenticationHook extends AuthenticationHook
{
    public function getLogoutRedirectUrl(User $user): ?Url
    {
        return Url::fromPath('https://other-idp.example.com/logout');
    }
}

class AuthenticationHookTest extends BaseTestCase
{
    public function tearDown(): void
    {
        Hook::clean();

        parent::tearDown();
    }

    public function testGetLogoutRedirectUrlFromHooksSkipsFailingHooksAndReturnsFirstUrl(): void
    {
        Hook::clean();
        FailingAuthenticationHook::register(true);
        IdentityProviderAuthenticationHook::register(true);
        OtherIdentityProviderAuthenticationHook::register(true);

        $this->assertSame(
            'https://idp.example.com/logout',
            AuthenticationHook::getLogoutRedirectUrlFromHooks(new User('jdoe'))?->getAbsoluteUrl()
        );
    }
}
