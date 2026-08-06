<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Forms\Config\Security;

use Icinga\Application\Config;
use Icinga\Forms\Config\Security\CspConfigForm;
use Icinga\Test\BaseTestCase;

class CspConfigFormTest extends BaseTestCase
{
    public function testDirectiveCheckboxesDefaultOnWhenStrictCspIsUnconfigured(): void
    {
        $this->assertDirectiveDefaults([], '1');
    }

    public function testDirectiveCheckboxesDefaultOnWhenStrictCspWasDisabled(): void
    {
        $this->assertDirectiveDefaults(['security' => ['use_strict_csp' => '0']], '1');
    }

    public function testDirectiveCheckboxesDefaultOffWhenStrictCspWasEnabled(): void
    {
        $this->assertDirectiveDefaults(['security' => ['use_strict_csp' => '1']], '0');
    }

    public function testConfiguredDirectiveCheckboxValuesOverrideDefaults(): void
    {
        $form = $this->createDirectiveForm([
            'security' => [
                'use_strict_csp'        => '1',
                'csp_enable_modules'    => '1',
                'csp_enable_dashboards' => '1',
                'csp_enable_navigation' => '1',
            ],
        ]);

        $this->assertSame('1', $form->getValue('security__csp_enable_modules'));
        $this->assertSame('1', $form->getValue('security__csp_enable_dashboards'));
        $this->assertSame('1', $form->getValue('security__csp_enable_navigation'));
    }

/**
 * @param array{security?: array<string, string>} $configData
 */
private function assertDirectiveDefaults(array $configData, string $expected): void
    {
        $form = $this->createDirectiveForm($configData);

        foreach (['csp_enable_modules', 'csp_enable_dashboards', 'csp_enable_navigation'] as $key) {
            $this->assertSame($expected, $form->getValue('security__' . $key), $key);
        }
    }

/**
 * @param array{security?: array<string, string>} $configData
 */
private function createDirectiveForm(array $configData): CspConfigForm
    {
        $form = new class (Config::fromArray($configData)) extends CspConfigForm {
            protected function assemble(): void
            {
            }

            public function addDirectiveCheckboxForTest(string $key): void
            {
                $this->addDirectiveCheckboxElement($key, $key, $key, true);
            }
        };
        $form->disableCsrfCounterMeasure();

        foreach (['csp_enable_modules', 'csp_enable_dashboards', 'csp_enable_navigation'] as $key) {
            $form->addDirectiveCheckboxForTest($key);
        }

        return $form;
    }
}
