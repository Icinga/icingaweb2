<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Authentication\AuthChain;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\User;
use PHPUnit\Framework\Attributes\DataProvider;

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

    /**
     * @return iterable<string, array{array<string, array<string, string>>, bool}>
     */
    public static function backendConfigs(): iterable
    {
        yield 'no backends' => [[], false];
        yield 'external only' => [['autologin' => ['backend' => 'external']], false];
        yield 'external with other casing' => [['autologin' => ['backend' => 'External']], false];
        yield 'disabled database' => [
            ['db' => ['backend' => 'db', 'resource' => 'icingaweb', 'disabled' => '1']],
            false
        ];
        yield 'enabled database' => [['db' => ['backend' => 'db', 'resource' => 'icingaweb']], true];
        yield 'enabled database with explicit enabled' => [
            ['db' => ['backend' => 'db', 'resource' => 'icingaweb', 'disabled' => '0']],
            true
        ];
        yield 'external and database' => [
            ['autologin' => ['backend' => 'external'], 'db' => ['backend' => 'db', 'resource' => 'icingaweb']],
            true
        ];
        yield 'database without resource' => [['db' => ['backend' => 'db']], true];
        yield 'section without backend type' => [['broken' => ['resource' => 'icingaweb']], true];
    }

    /**
     * @param array<string, array<string, string>> $sections
     */
    #[DataProvider('backendConfigs')]
    public function testHasPasswordBackend(array $sections, bool $expected): void
    {
        $chain = new AuthChain(new Config(new ConfigObject($sections)));

        $this->assertSame($expected, $chain->hasPasswordBackend());
    }

    public function testHasPasswordBackendIfConfigurationIsUnreadable(): void
    {
        $this->assertTrue($this->createChainWithUnreadableConfig()->hasPasswordBackend());
    }

    public function testHasPasswordBackendIfConfigurationIsUnreadableAfterAuthentication(): void
    {
        $chain = $this->createChainWithUnreadableConfig();
        $chain->authenticate(new User('jdoe'), 'secret');

        $this->assertTrue($chain->hasPasswordBackend());
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
