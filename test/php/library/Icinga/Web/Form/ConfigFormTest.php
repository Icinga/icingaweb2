<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Form;

use Icinga\Application\Config;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\ConfigForm;

class MockConfigForm extends ConfigForm
{
    protected function assemble(): void
    {
        $this->addButtonElements();
    }

    public function exposeGetIniKeyFromName(string $name): ?array
    {
        return $this->getIniKeyFromName($name);
    }

    public function exposeSetSectionKeyDelimiter(string $delimiter): void
    {
        $this->sectionKeyDelimiter = $delimiter;
    }
}

class ConfigFormTest extends BaseTestCase
{
    private function makeForm(array $configData = []): MockConfigForm
    {
        return new MockConfigForm(Config::fromArray($configData));
    }

    public function testGetIniKeyFromNameSplitsOnDoubleUnderscore(): void
    {
        $form = $this->makeForm();
        $this->assertSame(['section', 'key'], $form->exposeGetIniKeyFromName('section__key'));
    }

    public function testGetIniKeyFromNameReturnsNullWithoutDelimiter(): void
    {
        $form = $this->makeForm();
        $this->assertNull($form->exposeGetIniKeyFromName('nodelimiter'));
    }

    public function testGetIniKeyFromNameSplitsOnFirstDelimiterOnly(): void
    {
        $form = $this->makeForm();
        $this->assertSame(['a', 'b__c'], $form->exposeGetIniKeyFromName('a__b__c'));
    }

    public function testGetIniKeyFromNameDefaultDelimiterDoesNotMatchSingleUnderscore(): void
    {
        $form = $this->makeForm();
        $this->assertNull($form->exposeGetIniKeyFromName('section_key'));
    }

    public function testGetIniKeyFromNameUsesCustomDelimiter(): void
    {
        $form = $this->makeForm();
        $form->exposeSetSectionKeyDelimiter('|');
        $this->assertSame(['section', 'key'], $form->exposeGetIniKeyFromName('section|key'));
    }

    public function testCustomDelimiterNoLongerMatchesDefaultDoubleUnderscore(): void
    {
        $form = $this->makeForm();
        $form->exposeSetSectionKeyDelimiter('|');
        $this->assertNull($form->exposeGetIniKeyFromName('section__key'));
    }

    public function testGetConfigValueReturnsValueFromConfig(): void
    {
        $form = $this->makeForm(['section' => ['key' => 'value']]);
        $this->assertSame('value', $form->getConfigValue('section__key'));
    }

    public function testGetConfigValueReturnsNullByDefault(): void
    {
        $form = $this->makeForm(['section' => []]);
        $this->assertNull($form->getConfigValue('section__missing'));
    }

    public function testGetConfigValueReturnsProvidedDefault(): void
    {
        $form = $this->makeForm(['section' => []]);
        $this->assertSame('fallback', $form->getConfigValue('section__missing', 'fallback'));
    }

    public function testGetConfigValueReturnsDefaultWhenNameHasNoDelimiter(): void
    {
        $form = $this->makeForm();
        $this->assertSame('default', $form->getConfigValue('nodelimiter', 'default'));
    }

    public function testGetConfigValueReturnsDefaultForMissingSection(): void
    {
        $form = $this->makeForm();
        $this->assertSame('default', $form->getConfigValue('missing__key', 'default'));
    }

    public function testGetConfigValueUsesCustomDelimiter(): void
    {
        $form = $this->makeForm(['section' => ['key' => 'value']]);
        $form->exposeSetSectionKeyDelimiter('|');
        $this->assertSame('value', $form->getConfigValue('section|key'));
        $this->assertNull($form->getConfigValue('section__key'));
    }

    public function testSubmitButtonIsAddedAfterAssembly(): void
    {
        $form = $this->makeForm();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('store'));
    }
}
