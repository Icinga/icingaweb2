<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Form;

use Icinga\Application\Config;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\ConfigSectionForm;
use Icinga\Web\Session;
use LogicException;

class MockConfigSectionForm extends ConfigSectionForm
{
    public function exposeGetIniKeyFromName(string $name): ?array
    {
        return $this->getIniKeyFromName($name);
    }

    public function exposeAddSectionNameElement(array $params = []): void
    {
        $this->addSectionNameElement($params);
    }
}

class ConfigSectionFormTest extends BaseTestCase
{
    private function makeCreateForm(array $configData = []): MockConfigSectionForm
    {
        return (new MockConfigSectionForm(Config::fromArray($configData)))
            ->setCsrfCounterMeasureId(Session::getSession()->getId());
    }

    private function makeEditForm(string $section = 'mysection', array $configData = []): MockConfigSectionForm
    {
        return (new MockConfigSectionForm(Config::fromArray($configData), $section))
            ->setCsrfCounterMeasureId(Session::getSession()->getId());
    }

    public function testIsCreateFormWhenSectionIsNull(): void
    {
        $form = $this->makeCreateForm();
        $this->assertTrue($form->isCreateForm());
    }

    public function testIsNotCreateFormWhenSectionIsProvided(): void
    {
        $form = $this->makeEditForm();
        $this->assertFalse($form->isCreateForm());
    }

    public function testAllowDeletionIsTrueByDefaultForEditForm(): void
    {
        $form = $this->makeEditForm();
        $this->assertTrue($form->allowDeletion());
    }

    public function testAllowDeletionCanBeDisabledOnEditForm(): void
    {
        $form = $this->makeEditForm();
        $form->setAllowDeletion(false);
        $this->assertFalse($form->allowDeletion());
    }

    public function testSetAllowDeletionIsFluentSetter(): void
    {
        $form = $this->makeEditForm();
        $this->assertSame($form, $form->setAllowDeletion(false));
    }

    public function testSetAllowDeletionThrowsOnCreateForm(): void
    {
        $this->expectException(LogicException::class);

        $form = $this->makeCreateForm();
        $form->setAllowDeletion(false);
    }

    public function testSetAllowDeletionThrowsAfterAssembled(): void
    {
        $this->expectException(LogicException::class);

        $form = $this->makeEditForm();
        $form->ensureAssembled();
        $form->setAllowDeletion(false);
    }

    public function testAllowRenameIsTrueByDefaultForEditForm(): void
    {
        $form = $this->makeEditForm();
        $this->assertTrue($form->allowRename());
    }

    public function testAllowRenameCanBeDisabledOnEditForm(): void
    {
        $form = $this->makeEditForm();
        $form->setAllowRename(false);
        $this->assertFalse($form->allowRename());
    }

    public function testSetAllowRenameIsFluentSetter(): void
    {
        $form = $this->makeEditForm();
        $this->assertSame($form, $form->setAllowRename(false));
    }

    public function testSetAllowRenameThrowsOnCreateForm(): void
    {
        $this->expectException(LogicException::class);

        $form = $this->makeCreateForm();
        $form->setAllowRename(false);
    }

    public function testSetAllowRenameThrowsAfterAssembled(): void
    {
        $this->expectException(LogicException::class);

        $form = $this->makeEditForm();
        $form->ensureAssembled();
        $form->setAllowRename(false);
    }

    public function testGetIniKeyFromNameReturnsSectionAndKeyForEditForm(): void
    {
        $form = $this->makeEditForm('mysection');
        $this->assertSame(['mysection', 'mykey'], $form->exposeGetIniKeyFromName('mykey'));
    }

    public function testGetIniKeyFromNameIgnoresDoubleUnderscoreConvention(): void
    {
        $form = $this->makeEditForm('mysection');
        $this->assertSame(
            ['mysection', 'key__with__underscores'],
            $form->exposeGetIniKeyFromName('key__with__underscores'),
        );
    }

    public function testGetIniKeyFromNameReturnsNullForCreateForm(): void
    {
        $form = $this->makeCreateForm();
        $this->assertNull($form->exposeGetIniKeyFromName('anykey'));
    }

    public function testShouldDeleteReturnsFalseWhenNoDeleteButtonExists(): void
    {
        $form = $this->makeEditForm();
        $this->assertFalse($form->shouldDelete());
    }

    public function testDeleteButtonIsAddedForEditFormWithDeletionAllowed(): void
    {
        $form = $this->makeEditForm();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('delete'));
    }

    public function testDeleteButtonIsNotAddedForCreateForm(): void
    {
        $form = $this->makeCreateForm();
        $form->ensureAssembled();
        $this->assertFalse($form->hasElement('delete'));
    }

    public function testDeleteButtonIsNotAddedWhenDeletionIsDisabled(): void
    {
        $form = $this->makeEditForm();
        $form->setAllowDeletion(false);
        $form->ensureAssembled();
        $this->assertFalse($form->hasElement('delete'));
    }

    public function testSubmitButtonIsAlwaysAdded(): void
    {
        $form = $this->makeEditForm();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('store'));
    }

    public function testNameElementIsAddedForCreateForm(): void
    {
        $form = $this->makeCreateForm();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('name'));
    }

    public function testNameElementIsAddedForEditFormWithRenameAllowed(): void
    {
        $form = $this->makeEditForm();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('name'));
    }

    public function testNameElementIsNotAddedForEditFormWhenRenameIsDisabled(): void
    {
        $form = $this->makeEditForm();
        $form->setAllowRename(false);
        $form->ensureAssembled();
        $this->assertFalse($form->hasElement('name'));
    }

    public function testAutomaticNameElementIsAddedInFirstPlace(): void
    {
        $form = $this->makeEditForm();
        $form->ensureAssembled();
        $content = $form->getContent();
        $this->assertEquals(3, count($content));
        $this->assertEquals('name', $content[0]->getName());
    }

    public function testNameElementIsNotAddedTwice(): void
    {
        $form = $this->makeEditForm();
        $form->exposeAddSectionNameElement();
        $form->ensureAssembled();
        $content = $form->getContent();
        $this->assertEquals(3, count($content));
    }
}
