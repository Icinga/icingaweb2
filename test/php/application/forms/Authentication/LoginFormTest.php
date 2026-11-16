<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Authentication;

use Icinga\Application\Config;
use Icinga\Authentication\Auth;
use Icinga\Authentication\AuthChain;
use Icinga\Data\ConfigObject;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\Test\BaseTestCase;
use Mockery;
use ReflectionProperty;

class LoginFormTest extends BaseTestCase
{
    public function tearDown(): void
    {
        (new ReflectionProperty(Auth::class, 'instance'))->setValue(null, null);

        parent::tearDown();
    }

    public function testOnRequestReportsUnreadableAuthenticationConfiguration(): void
    {
        $form = $this->createFormWithAuthChain(new class (new Config()) extends AuthChain {
            public function __construct(Config $config)
            {
                parent::__construct($config);

                $this->error = static::EPERM;
            }
        });

        $form->onRequest();

        $this->assertSame(
            ['The authentication configuration is not readable.'],
            $form->getMessages()
        );
    }

    public function testOnRequestReportsOnlyExternalBackends(): void
    {
        $form = $this->createFormWithAuthChain(
            new AuthChain(new Config(new ConfigObject(['autologin' => ['backend' => 'external']])))
        );

        $form->onRequest();

        $this->assertCount(1, $form->getMessages());
        $this->assertStringContainsString('not authenticated using any of the web server', $form->getMessages()[0]);
    }

    protected function createFormWithAuthChain(AuthChain $authChain): LoginForm
    {
        $auth = Mockery::mock(Auth::class);
        $auth->shouldReceive('getAuthChain')->andReturn($authChain);
        (new ReflectionProperty(Auth::class, 'instance'))->setValue(null, $auth);

        return new LoginForm();
    }
}
