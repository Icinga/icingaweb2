<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Config\General;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Hook;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use Icinga\Forms\Config\General\PasswordPolicyConfigForm;
use Icinga\Forms\Config\Security\CspConfigForm;
use Icinga\Test\BaseTestCase;
use ipl\Html\FormElement\SelectElement;
use ipl\Html\ValidHtml;
use RuntimeException;
use SensitiveParameter;

/**
 * A policy that cannot be enumerated
 *
 * Enumerating all policies sorts them by display name, so throwing here is what
 * a policy provided by a broken module looks like to the configuration form.
 */
class BrokenPasswordPolicy extends PasswordPolicyHook
{
    public function getDisplayName(): string
    {
        throw new RuntimeException('Broken policy');
    }

    public function getName(): string
    {
        return 'broken';
    }

    public function getDescription(): ?ValidHtml
    {
        return null;
    }

    public function validate(
        #[SensitiveParameter] string $newPassword,
        #[SensitiveParameter] ?string $oldPassword = null,
    ): array {
        return [];
    }
}

class PasswordPolicyConfigFormTest extends BaseTestCase
{
    public const ELEMENT_NAME = 'security__password_policy';

    public const UNAVAILABLE_POLICY = 'gone/strict';

    public function setUp(): void
    {
        parent::setUp();

        Hook::clean();
        AnyPasswordPolicy::register();
        CommonPasswordPolicy::register();
    }

    public function tearDown(): void
    {
        Hook::clean();

        parent::tearDown();
    }

    public function testUnconfiguredPolicyDefaultsToTheUnrestrictedOne(): void
    {
        $form = $this->createForm();

        $this->assertSame(
            PasswordPolicyHook::DEFAULT_PASSWORD_POLICY,
            $form->getElement(static::ELEMENT_NAME)->getValue(),
        );
        $this->assertTrue($form->isValid());
    }

    public function testUnavailablePolicyHandling(): void
    {
        $form = $this->createForm(['security' => ['password_policy' => static::UNAVAILABLE_POLICY]]);

        /** @var SelectElement $element */
        $element = $form->getElement(static::ELEMENT_NAME);
        $option = $element->getOption(static::UNAVAILABLE_POLICY);

        $this->assertNotNull($option);
        $this->assertTrue($option->getAttributes()->get('disabled')->getValue());
        $this->assertStringContainsString('selected', (string) $option);
        $this->assertSame(static::UNAVAILABLE_POLICY, $element->getValue());
        $this->assertSame(static::UNAVAILABLE_POLICY . ' (unknown)', $option->getLabel());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->hasElement('store'));
        $this->assertSame(true, $form->getElement('store')->getAttribute('disabled')->getValue() === true);
    }

    public function testPoliciesThatFailToEnumerateLeaveTheFormUnstorable(): void
    {
        BrokenPasswordPolicy::register();

        $form = $this->createForm();

        $this->assertTrue($form->hasElement('store'));
        $this->assertSame(true, $form->getElement('store')->getAttribute('disabled')->getValue() === true);
        $this->assertFalse($form->isValid());
    }

    public function testASubmitIsRejectedAlthoughTheControlIsOnlyDisabled(): void
    {
        BrokenPasswordPolicy::register();

        $form = new class (Config::fromArray([])) extends PasswordPolicyConfigForm {
            public bool $saved = false;

            protected function save(): void
            {
                $this->saved = true;
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/config/security'))
                ->withParsedBody(['uid' => 'form_password_policy_config', 'store' => 'Store'])
        );

        // A disabled control only keeps the browser from offering it. A request carrying
        // the control's name still arrives as a submit, so the form itself has to refuse.
        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertFalse($form->saved);
    }

    /**
     * @param array<string, array<string, string>> $configData The application configuration to back the form with
     */
    protected function createForm(array $configData = []): PasswordPolicyConfigForm
    {
        $form = new PasswordPolicyConfigForm(Config::fromArray($configData));
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        return $form;
    }
}
