<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Form;

use Error;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Form\ConfigForm;
use ipl\Html\FormElement\PasswordElement;
use LogicException;

class ConfigFormTest extends BaseTestCase
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

    public function testSubmitButtonIsAddedAfterAssembly(): void
    {
        $form = $this->makeForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('store'));
    }

    public function testCsrfElementIsAddedAfterAssembly(): void
    {
        $form = $this->makeForm();
        $form->setCsrfCounterMeasureId('bogus');
        $form->ensureAssembled();
        $this->assertTrue($form->hasElement('CSRFToken'));
        $this->assertTrue($form->getElement('CSRFToken')->isRequired());
        $this->assertMatchesRegularExpression(
            '/ value="[^"]+\|[^"]+"/',
            $form->getElement('CSRFToken')->render()
        );
    }

    public function testDisabledCsrfCounterMeasureDoesNotAddElement(): void
    {
        $form = $this->makeForm();
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $this->assertFalse($form->hasElement('CSRFToken'));
        $this->assertTrue($form->hasElement('store'));
    }

    public function testAssemblyWithoutCsrfCounterMeasureIdFails(): void
    {
        $form = $this->makeForm();
        $this->expectException(Error::class);
        $this->expectExceptionMessage('No CSRF counter measure ID set');
        $form->ensureAssembled();
    }

    public function testRepeatedAssemblyDoesNotDuplicateRequiredElements(): void
    {
        $form = $this->makeForm();
        $form->setCsrfCounterMeasureId('bogus');
        $form->ensureAssembled();
        $submitElement = $form->getElement('store');
        $csrfElement = $form->getElement('CSRFToken');
        $form->ensureAssembled();
        $this->assertCount(2, $form->getElements());
        $this->assertSame($submitElement, $form->getElement('store'));
        $this->assertSame($csrfElement, $form->getElement('CSRFToken'));
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
        $form->disableCsrfCounterMeasure();
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
        $form->disableCsrfCounterMeasure();
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
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();
        $form->populate(['mysection__key' => '']);
        $form->exposeSave();

        $this->assertFalse($config->hasSection('mysection'));
    }

    private function makeForm(): ConfigForm
    {
        return new class (Config::fromArray([])) extends ConfigForm {
        };
    }
}
