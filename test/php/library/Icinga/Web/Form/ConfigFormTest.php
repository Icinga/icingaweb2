<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Form;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\ConfigForm;
use ipl\Html\FormElement\PasswordElement;
use LogicException;

class ConfigFormTest extends BaseTestCase
{
    private function makeForm(array $configData = [])
    {
        return new class(Config::fromArray($configData)) extends ConfigForm {
            public function exposeSetSectionKeyDelimiter(string $delimiter): void
            {
                $this->sectionKeyDelimiter = $delimiter;
            }
        };
    }

    public function testSubmitButtonIsAddedAfterAssembly(): void
    {
        $form = $this->makeForm();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('store'));
    }

    public function testSaveThrowsForArrayElementValue(): void
    {
        $this->expectException(LogicException::class);

        $config = new class(new ConfigObject([])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void {}
        };

        $form = new class($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('select', 'mysection__key', [
                    'options' => ['a' => 'A', 'b' => 'B'],
                    'multiple' => true,
                ]);
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->ensureAssembled();
        $form->populate(['mysection__key' => ['a', 'b']]);
        $form->exposeSave();
    }

    public function testUnchangedPasswordElementRetainsConfigValueOnSave(): void
    {
        $config = new class(new ConfigObject(['mysection' => ['password' => 'secret']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void {}
        };

        $form = new class($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('password', 'mysection__password');
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->populate(['mysection__password' => PasswordElement::DUMMYPASSWORD]);
        $form->ensureAssembled();
        $form->exposeSave();

        $this->assertSame('secret', $config->get('mysection', 'password'));
    }

    public function testEmptySectionIsRemovedOnSave(): void
    {
        $config = new class(new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void {}
        };

        $form = new class($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key');
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->ensureAssembled();
        $form->populate(['mysection__key' => '']);
        $form->exposeSave();

        $this->assertFalse($config->hasSection('mysection'));
    }
}
