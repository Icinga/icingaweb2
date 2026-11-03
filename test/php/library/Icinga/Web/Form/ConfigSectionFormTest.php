<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Form;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\ConfigSectionForm;
use LogicException;

class ConfigSectionFormTest extends BaseTestCase
{
    /** @var mixed Original value restored to avoid leaking global test state */
    private mixed $secFetchSiteHeader;

    public function setUp(): void
    {
        parent::setUp();

        $this->secFetchSiteHeader = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null;
        // Force token protection because safe Sec-Fetch-Site values use a dummy element.
        unset($_SERVER['HTTP_SEC_FETCH_SITE']);
    }

    public function tearDown(): void
    {
        if ($this->secFetchSiteHeader === null) {
            unset($_SERVER['HTTP_SEC_FETCH_SITE']);
        } else {
            $_SERVER['HTTP_SEC_FETCH_SITE'] = $this->secFetchSiteHeader;
        }

        parent::tearDown();
    }

    private function makeCreateForm(array $configData = [])
    {
        return new class(Config::fromArray($configData)) extends ConfigSectionForm {
            public function exposeAddSectionNameElement(array $params = []): void
            {
                $this->addSectionNameElement($params);
            }
        };
    }

    private function makeEditForm(string $section = 'mysection', array $configData = [])
    {
        return new class(Config::fromArray($configData), $section) extends ConfigSectionForm {
            public function exposeAddSectionNameElement(array $params = []): void
            {
                $this->addSectionNameElement($params);
            }
        };
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
        $form->disableCsrfCounterMeasure();
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
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->setAllowRename(false);
    }

    public function testShouldDeleteReturnsFalseWhenNoDeleteButtonExists(): void
    {
        $form = $this->makeEditForm();
        $this->assertFalse($form->shouldDelete());
    }

    public function testDeleteButtonIsAddedForEditFormWithDeletionAllowed(): void
    {
        $form = $this->makeEditForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('delete'));
    }

    public function testDeleteButtonIsNotAddedForCreateForm(): void
    {
        $form = $this->makeCreateForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertFalse($form->hasElement('delete'));
    }

    public function testDeleteButtonIsNotAddedWhenDeletionIsDisabled(): void
    {
        $form = $this->makeEditForm();
        $form->setAllowDeletion(false);
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertFalse($form->hasElement('delete'));
    }

    public function testSubmitButtonIsAlwaysAdded(): void
    {
        $form = $this->makeEditForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('store'));
    }

    public function testNameElementIsAddedForCreateForm(): void
    {
        $form = $this->makeCreateForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('name'));
    }

    public function testNameElementIsAddedForEditFormWithRenameAllowed(): void
    {
        $form = $this->makeEditForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('name'));
    }

    public function testNameElementIsNotAddedForEditFormWhenRenameIsDisabled(): void
    {
        $form = $this->makeEditForm();
        $form->setAllowRename(false);
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertFalse($form->hasElement('name'));
    }

    public function testAutomaticNameElementIsAddedInFirstPlace(): void
    {
        $form = $this->makeEditForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $content = $form->getContent();
        $this->assertEquals(2, count($content));
        $this->assertEquals('name', $content[0]->getName());
    }

    public function testNameElementIsNotAddedTwice(): void
    {
        $form = $this->makeEditForm();
        $form->disableCsrfCounterMeasure();
        $form->exposeAddSectionNameElement();
        $form->ensureAssembled();
        $content = $form->getContent();
        $this->assertEquals(2, count($content));
    }

    public function testNameElementIsNotAddedTwiceForCreateForm(): void
    {
        $form = $this->makeCreateForm();
        $form->disableCsrfCounterMeasure();
        $form->exposeAddSectionNameElement();
        $form->ensureAssembled();
        $content = $form->getContent();
        $this->assertEquals(2, count($content));
    }

    public function testSaveThrowsForArrayElementValue(): void
    {
        $this->expectException(LogicException::class);

        $config = new class(new ConfigObject([])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void {}
        };

        $form = new class($config, 'mysection') extends ConfigSectionForm {
            protected function assemble(): void
            {
                $this->addElement('select', 'key', [
                    'options' => ['a' => 'A', 'b' => 'B'],
                    'multiple' => true,
                ]);
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->populate(['key' => ['a', 'b']]);
        $form->exposeSave();
    }

    public function testEmptySectionIsPersistedOnSave(): void
    {
        $config = new class(new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void {}
        };

        $form = new class($config, 'mysection') extends ConfigSectionForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'key');
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->populate(['key' => '']);
        $form->exposeSave();

        $this->assertTrue($config->hasSection('mysection'));
    }

    public function testEmptySectionIsCreatedOnSaveEvenWithNoKeys(): void
    {
        $config = new class(new ConfigObject([])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void {}
        };

        $form = new class($config, 'newsection') extends ConfigSectionForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'key');
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->populate(['key' => '']);
        $form->exposeSave();

        $this->assertTrue($config->hasSection('newsection'));
    }
}
