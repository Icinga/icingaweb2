<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Authentication\AuthChain;
use Icinga\Test\BaseTestCase;
use Icinga\User;

class AuthChainTest extends BaseTestCase
{
    public function testAuthenticateKeepsReadErrorOfConfiguration(): void
    {
        $chain = $this->createChainWithUnreadableConfig();

        $this->assertFalse($chain->authenticate(new User('jdoe'), 'secret'));
        $this->assertSame(AuthChain::EPERM, $chain->getError());

        $this->assertFalse($chain->authenticate(new User('jdoe'), 'secret'));
        $this->assertSame(AuthChain::EPERM, $chain->getError());
    }

    protected function createChainWithUnreadableConfig(): AuthChain
    {
        return new class (new Config()) extends AuthChain {
            public function __construct(Config $config)
            {
                parent::__construct($config);

                $this->error = static::EPERM;
            }
        };
    }
}
