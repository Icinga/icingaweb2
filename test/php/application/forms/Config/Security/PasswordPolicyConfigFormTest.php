<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Config\Security;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Config;
use Icinga\Application\Hook;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
use Icinga\Application\ProvidedHook\CommonPasswordPolicy;
use Icinga\Data\ConfigObject;
use Icinga\Forms\Config\Security\PasswordPolicyConfigForm;
use Icinga\Test\BaseTestCase;
use ipl\Html\FormElement\SelectElement;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Icinga\Lib\Authentication\BrokenPasswordPolicy;

class PasswordPolicyConfigFormTest extends BaseTestCase
{
    /** @var string Name of the password policy selection element */
    public const ELEMENT_NAME = 'security__password_policy';

    /** @var string Canonical name of an unavailable password policy */
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
        $this->assertTrue($form->getElement('store')->getAttribute('disabled')->getValue());
    }

    public function testPoliciesThatFailToEnumerateLeaveTheFormUnstorable(): void
    {
        BrokenPasswordPolicy::register();

        $form = $this->createForm();

        $this->assertTrue($form->hasElement('store'));
        $this->assertTrue($form->getElement('store')->getAttribute('disabled')->getValue());
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

    public function testStoredPolicyIsSelected(): void
    {
        $form = $this->createForm(['security' => ['password_policy' => 'common']]);

        $this->assertSame('common', $form->getElement(static::ELEMENT_NAME)->getValue());
        $this->assertTrue($form->isValid());
    }

    public function testStoringKeepsOtherSecurityKeys(): void
    {
        $config = $this->createUnsavedConfig(['security' => ['use_strict_csp' => '1']]);

        $form = $this->submit($config, 'common');

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertSame('common', $config->get('security', 'password_policy'));
        $this->assertSame('1', $config->get('security', 'use_strict_csp'));
    }

    public function testStoringTheDefaultPolicyRemovesTheKey(): void
    {
        $config = $this->createUnsavedConfig(['security' => ['password_policy' => 'common']]);

        $this->submit($config, PasswordPolicyHook::DEFAULT_PASSWORD_POLICY);

        $this->assertNull($config->get('security', 'password_policy'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emptyPolicyProvider(): array
    {
        return [
            'empty'      => [''],
            'whitespace' => ['   '],
        ];
    }

    #[DataProvider('emptyPolicyProvider')]
    public function testEmptySubmittedPolicyIsRejected(string $policy): void
    {
        $config = $this->createUnsavedConfig(['security' => ['password_policy' => 'common']]);

        // The browser offers no empty option, but a crafted request can submit one.
        $form = $this->submit($config, $policy);

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertSame('common', $config->get('security', 'password_policy'));
    }

    public function testSubmitOfAnotherFormIsIgnored(): void
    {
        $config = $this->createUnsavedConfig();

        // The CSP form on the same page submits with its own form UID.
        $form = $this->submit($config, 'common', 'csp_config');

        $this->assertFalse($form->hasBeenSubmitted());
        $this->assertNull($config->get('security', 'password_policy'));
    }

    public function testSelectionCanBeStoredWhileTheConfiguredPolicyIsUnavailable(): void
    {
        $config = $this->createUnsavedConfig(['security' => ['password_policy' => static::UNAVAILABLE_POLICY]]);

        $form = $this->submit($config, 'common');

        $this->assertStringContainsString('Could not load the configured password policy', $form->render());
        $this->assertSame('common', $config->get('security', 'password_policy'));
    }

    public function testNonStringSubmittedPolicyDoesNotSaveConfiguration(): void
    {
        $config = $this->createUnsavedConfig(['security' => ['password_policy' => 'common']]);
        $form = new class ($config) extends PasswordPolicyConfigForm {
            public bool $saved = false;

            protected function save(): void
            {
                $this->saved = true;
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/config/security'))->withParsedBody([
                'uid'                => 'form_password_policy_config',
                static::ELEMENT_NAME => ['common'],
                'store'              => 'Store',
            ]),
        );

        $this->assertTrue($form->hasBeenSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertFalse($form->saved);
        $this->assertSame('common', $config->get('security', 'password_policy'));
        $this->assertStringContainsString('The password policy must be a string', $form->render());
    }

    public function testMalformedIniPolicyCanBeReplacedWithoutChangingOtherSecuritySettings(): void
    {
        $configFile = tempnam(sys_get_temp_dir(), 'icinga-password-policy-');
        $this->assertNotFalse($configFile);

        try {
            $ini = "[security]\npassword_policy[] = \"common\"\nuse_strict_csp = \"1\"\n";
            file_put_contents($configFile, $ini);
            $config = Config::fromIni($configFile);
            $originalPolicy = $config->get('security', 'password_policy');

            $form = new PasswordPolicyConfigForm($config);
            $form->disableCsrfCounterMeasure();
            $form->handleRequest(new ServerRequest('GET', '/icingaweb2/config/security'));

            /** @var SelectElement $element */
            $element = $form->getElement(static::ELEMENT_NAME);
            $this->assertNull($element->getValue());
            $this->assertNotNull($element->getOption('common'));
            $this->assertFalse($element->getOption('common')->getAttributes()->get('disabled')->getValue());
            $this->assertStringContainsString('Invalid password policy', $form->render());
            $this->assertFalse($form->isValid());
            $this->assertSame($originalPolicy, $config->get('security', 'password_policy'));
            $this->assertSame($ini, file_get_contents($configFile));

            // Submitting without a replacement must preserve the malformed configuration.
            $unselectedForm = new PasswordPolicyConfigForm($config);
            $unselectedForm->disableCsrfCounterMeasure();
            $unselectedForm->handleRequest(
                (new ServerRequest('POST', '/icingaweb2/config/security'))->withParsedBody([
                    'uid'   => 'form_password_policy_config',
                    'store' => 'Store',
                ]),
            );
            $this->assertFalse($unselectedForm->isValid());
            $this->assertSame($ini, file_get_contents($configFile));

            $recoveredForm = $this->submit($config, 'common');

            $this->assertTrue($recoveredForm->isValid());
            $this->assertTrue($recoveredForm->hasBeenSubmitted());
            $persistedConfig = Config::fromIni($configFile);
            $this->assertSame('common', $persistedConfig->get('security', 'password_policy'));
            $this->assertSame('1', $persistedConfig->get('security', 'use_strict_csp'));
        } finally {
            unlink($configFile);
        }
    }

    /**
     * @param array<string, array<string, string>> $configData The application configuration to back the form with
     *
     * @return PasswordPolicyConfigForm
     */
    protected function createForm(array $configData = []): PasswordPolicyConfigForm
    {
        $form = new PasswordPolicyConfigForm(Config::fromArray($configData));
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        return $form;
    }

    /**
     * Create a config that is never written to disk
     *
     * @param array<string, array<string, string>> $configData The initial configuration
     *
     * @return Config
     */
    protected function createUnsavedConfig(array $configData = []): Config
    {
        return new class (new ConfigObject($configData)) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };
    }

    /**
     * Submit the form backed by $config with the given policy selected
     *
     * @param Config $config The configuration to back the form with
     * @param string $policy The canonical name of the policy to select
     * @param string $uid The form UID to submit with
     *
     * @return PasswordPolicyConfigForm
     */
    protected function submit(
        Config $config,
        string $policy,
        string $uid = 'form_password_policy_config',
    ): PasswordPolicyConfigForm {
        $form = new PasswordPolicyConfigForm($config);
        $form->disableCsrfCounterMeasure();
        $form->handleRequest(
            (new ServerRequest('POST', '/icingaweb2/config/security'))->withParsedBody([
                'uid'                => $uid,
                static::ELEMENT_NAME => $policy,
                'store'              => 'Store',
            ]),
        );

        return $form;
    }
}
