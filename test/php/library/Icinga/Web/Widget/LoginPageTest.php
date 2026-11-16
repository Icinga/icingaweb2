<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Widget;

use Icinga\Authentication\LoginButton;
use Icinga\Authentication\LoginButtonForm;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Widget\LoginPage;
use ipl\Html\Text;
use ipl\Web\Compat\CompatForm;

class LoginPageTest extends BaseTestCase
{
    public function testRendersFormAndLoginButtons(): void
    {
        $html = (new LoginPage($this->createForm(), [$this->createLoginButtonForm()]))->render();

        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('name="submit_login"', $html);
        $this->assertStringContainsString('Login with Dex', $html);
    }

    public function testRendersOnlyLoginButtonsWithoutForm(): void
    {
        $html = (new LoginPage(null, [$this->createLoginButtonForm()]))->render();

        $this->assertStringNotContainsString('name="username"', $html);
        $this->assertStringNotContainsString('name="password"', $html);
        $this->assertStringNotContainsString('name="submit_login"', $html);
        $this->assertStringContainsString('Login with Dex', $html);
    }

    public function testRendersSetupNoteOnlyWhenRequested(): void
    {
        $this->assertStringContainsString(
            'class="config-note"',
            (new LoginPage($this->createForm(), [], true))->render()
        );
        $this->assertStringNotContainsString(
            'class="config-note"',
            (new LoginPage($this->createForm()))->render()
        );
    }

    protected function createForm(): CompatForm
    {
        return new class extends CompatForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'username');
                $this->addElement('password', 'password');
                $this->addElement('submit', 'submit_login', ['label' => 'Login']);
            }
        };
    }

    protected function createLoginButtonForm(): LoginButtonForm
    {
        return new LoginButtonForm('dex', new LoginButton(fn (): null => null, Text::create('Login with Dex')));
    }
}
