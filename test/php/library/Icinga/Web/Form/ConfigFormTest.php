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
use PHPUnit\Framework\Attributes\DataProvider;

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

    #[DataProvider('populationTimings')]
    public function testSaveThrowsForArrayElementValue(bool $populateBeforeAssembly): void
    {
        $this->expectException(LogicException::class);

        $config = new class(new ConfigObject([])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };

        $form = new class($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('select', 'mysection__key', [
                    'options'  => ['a' => 'A', 'b' => 'B'],
                    'multiple' => true,
                ]);
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->disableCsrfCounterMeasure();
        $this->populateAroundAssembly($form, [['mysection__key' => ['a', 'b']]], $populateBeforeAssembly);
        $form->exposeSave();
    }

    #[DataProvider('populationTimings')]
    public function testUnchangedPasswordElementRetainsConfigValueOnSave(bool $populateBeforeAssembly): void
    {
        $config = new class(new ConfigObject(['mysection' => ['password' => 'secret']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
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
        $this->populateAroundAssembly(
            $form,
            [['mysection__password' => PasswordElement::DUMMYPASSWORD]],
            $populateBeforeAssembly,
        );
        $form->exposeSave();

        $this->assertSame('secret', $config->get('mysection', 'password'));
    }

    public function testElementDefaultIsPreservedWhenConfigKeyIsNotSet(): void
    {
        $form = new class(Config::fromArray([])) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key', ['value' => 'defaultvalue']);
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertSame('defaultvalue', $form->getValue('mysection__key'));
        $this->assertSame('defaultvalue', $form->getPopulatedValue('mysection__key'));
    }

    public function testConfiguredValueOverridesElementDefault(): void
    {
        $form = new class (Config::fromArray(['mysection' => ['key' => 'configured']])) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key', ['value' => 'defaultvalue']);
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertSame('configured', $form->getValue('mysection__key'));
        $this->assertSame('configured', $form->getPopulatedValue('mysection__key'));
    }

    public function testConfiguredNullOverridesNonEmptyElementDefault(): void
    {
        $form = new class (Config::fromArray(['mysection' => ['key' => null]])) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key', ['value' => 'defaultvalue']);
            }
        };
        $form->disableCsrfCounterMeasure();
        $form->ensureAssembled();

        $this->assertNull($form->getValue('mysection__key'));
        $this->assertNull($form->getPopulatedValue('mysection__key'));
    }

    #[DataProvider('populationTimings')]
    public function testElementIsClearedWhenValueIsEmpty(bool $populateBeforeAssembly): void
    {
        $config = new class(new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };

        $form = new class($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key', []);
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->disableCsrfCounterMeasure();
        $this->populateAroundAssembly($form, [['mysection__key' => '']], $populateBeforeAssembly);
        $form->exposeSave();

        $this->assertNull($config->get('mysection', 'key'));
    }

    #[DataProvider('populationTimings')]
    public function testElementIsClearedWhenValueIsOriginalValue(bool $populateBeforeAssembly): void
    {
        $config = new class(new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };

        $form = new class($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key', ['value' => 'value']);
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->disableCsrfCounterMeasure();
        $this->populateAroundAssembly($form, [['mysection__key' => 'value']], $populateBeforeAssembly);
        $form->exposeSave();

        $this->assertNull($config->get('mysection', 'key'));
    }

    #[DataProvider('populationTimings')]
    public function testClearingFieldWithNonEmptyDefaultWritesEmptyStringToConfig(
        bool $populateBeforeAssembly,
    ): void {
        $config = new class(new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };

        $form = new class($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key', ['value' => 'default']);
            }

            public function exposeSave(): void
            {
                $this->save();
            }
        };
        $form->disableCsrfCounterMeasure();
        $this->populateAroundAssembly($form, [['mysection__key' => '']], $populateBeforeAssembly);
        $form->exposeSave();

        $this->assertTrue($config->hasSection('mysection'));
        $this->assertSame('', $config->get('mysection', 'key', 'not-set'));
    }

    #[DataProvider('populationTimings')]
    public function testEmptySectionIsRemovedOnSave(bool $populateBeforeAssembly): void
    {
        $config = new class(new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
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
        $this->populateAroundAssembly($form, [['mysection__key' => '']], $populateBeforeAssembly);
        $form->exposeSave();

        $this->assertFalse($config->hasSection('mysection'));
    }

    #[DataProvider('populationTimings')]
    public function testZeroValueIsSaved(bool $populateBeforeAssembly): void
    {
        $config = new class (new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };

        $form = new class ($config) extends ConfigForm {
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
        $this->populateAroundAssembly($form, [['mysection__key' => '0']], $populateBeforeAssembly);
        $form->exposeSave();

        $this->assertSame('0', $config->get('mysection', 'key'));
    }

    #[DataProvider('populationTimings')]
    public function testEveryPopulatedValueIsAppliedInOrder(bool $populateBeforeAssembly): void
    {
        $config = Config::fromArray(['mysection' => ['key' => 'configured']]);
        $form = new class ($config) extends ConfigForm {
            protected function assemble(): void
            {
                $this->addElement('text', 'mysection__key');
            }
        };
        $form->disableCsrfCounterMeasure();
        $this->populateAroundAssembly(
            $form,
            [['mysection__key' => ''], ['mysection__key' => '0']],
            $populateBeforeAssembly,
        );

        $this->assertSame('0', $form->getValue('mysection__key'));
        $this->assertSame('0', $form->getPopulatedValue('mysection__key'));
        $this->assertSame(
            ['configured', '', '0'],
            $form->getPopulatedValues('mysection__key'),
        );
    }

    #[DataProvider('populationTimings')]
    public function testNullValueClearsElement(bool $populateBeforeAssembly): void
    {
        $config = new class (new ConfigObject(['mysection' => ['key' => 'value']])) extends Config {
            public function saveIni($filePath = null, $fileMode = 0660): void
            {
            }
        };

        $form = new class ($config) extends ConfigForm {
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
        $this->populateAroundAssembly($form, [['mysection__key' => null]], $populateBeforeAssembly);
        $form->exposeSave();

        $this->assertNull($config->get('mysection', 'key'));
    }

    /** @return array<string, array{bool}> */
    public static function populationTimings(): array
    {
        return [
            'before assembly' => [true],
            'after assembly'  => [false],
        ];
    }

    /**
     * @param list<array<string, mixed>> $populations
     */
    private function populateAroundAssembly(ConfigForm $form, array $populations, bool $populateBeforeAssembly): void
    {
        if (! $populateBeforeAssembly) {
            $form->ensureAssembled();
        }

        foreach ($populations as $values) {
            $form->populate($values);
        }

        if ($populateBeforeAssembly) {
            $form->ensureAssembled();
        }
    }

    private function makeForm(): ConfigForm
    {
        return new class (Config::fromArray([])) extends ConfigForm {
        };
    }
}
