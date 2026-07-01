<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form;

use Exception;
use Icinga\Application\Config;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\ValueCandidates;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Str;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Compat\DisplayFormElement;
use ipl\Web\Widget\CopyToClipboard;
use LogicException;

/**
 * Form base-class providing standard functionality for configuration forms
 *
 *  Element names follow a `section__key` convention: the part before the
 *  {@see $sectionKeyDelimiter} (default: `__`) is the INI section name and the
 *  part after is the configuration key within that section. Subclasses add
 *  elements whose names match this pattern; `populateFromConfig` and `save`
 *  use it to map form values to and from the INI file automatically.
 */
class ConfigForm extends CompatForm
{
    /** @var string Name of the submit button element */
    protected const SUBMIT_BUTTON_NAME = 'store';

    /**
     * Delimiter used to separate the section and key in the element name
     *
     * This is used to determine whether an element is a configuration key or a
     * section. The default delimiter is '__', this is chosen to allow for section
     * names that contain underscores.
     *
     * @var string
     */
    protected string $sectionKeyDelimiter = '__';

    /**
     * Create a new configuration form
     *
     * @param Config $config The ini file configuration object to use for the form
     */
    public function __construct(
        protected Config $config,
    ) {
        $this->on(static::ON_ELEMENT_REGISTERED, function (FormElement $element) {
            [$section, $key] = Str::symmetricSplit($element->getName(), $this->sectionKeyDelimiter, 2);
            if ($key === null || $element->hasValue()) {
                return;
            }

            $configValue = $this->config->get($section, $key);
            if ($configValue === null) {
                return;
            }

            if (! ($element instanceof ValueCandidates)) {
                $element->setValue($configValue);

                return;
            }

            $candidates = $element->getValueCandidates();
            if (empty($candidates)) {
                // Initial render: no prior submission, set value and seed candidates
                $element->setValue($configValue);
                $element->setValueCandidates([$configValue]);
            } else {
                // POST: candidates were set by registerElement; append config value so
                // PasswordElement's (count > 1) condition resolves DUMMYPASSWORD on re-render
                $element->setValueCandidates(array_merge($candidates, [$configValue]));
            }
        });
    }

    /**
     * Ensure that all required form elements are present
     *
     * @return $this
     */
    public function ensureAssembled(): static
    {
        if (! $this->hasBeenAssembled) {
            parent::ensureAssembled();
            $this->addRequiredElements();
        }

        return $this;
    }

    /**
     * Create a hint that explains a configuration save failure and how to fix it manually
     *
     * @param Exception $exception The exception thrown while saving
     * @param Config $config The configuration that failed to save
     *
     * @return ValidHtml
     */
    public static function createConfigurationErrorHint(Exception $exception, Config $config): ValidHtml
    {
        $code = HtmlElement::create('code', null, (string) $config);
        CopyToClipboard::attachTo($code);

        return HtmlElement::create('div', null, [
            HtmlElement::create('h4', null, t('Saving Configuration Failed!')),
            HtmlElement::create('p', null, [
                sprintf(
                    t("The file %s couldn't be stored. (Error: '%s')"),
                    $config->getConfigFile(),
                    $exception->getMessage(),
                ),
                HtmlElement::create('br'),
                t('This could have one or more of the following reasons:'),
            ]),
            HtmlElement::create('ul', null, [
                HtmlElement::create('li', null, t("You don't have file-system permissions to write to the file")),
                HtmlElement::create('li', null, t('Something went wrong while writing the file')),
                HtmlElement::create('li', null, t(
                    "There's an application error preventing you from persisting the configuration",
                )),
            ]),
            HtmlElement::create('p', null, [
                t(
                    'Details can be found in the application log. ' .
                    "(If you don't have access to this log, call your administrator in this case)",
                ),
                HtmlElement::create('br'),
                t('In case you can access the file by yourself, you can open it and insert the config manually:'),
            ]),
            HtmlElement::create('p', null, HtmlElement::create('pre', null, $code)),
        ]);
    }

    /**
     * Persist the current configuration to disk
     *
     * If an error occurs, the form will be re-rendered with the error message
     * and the raw INI configuration.
     *
     * @return void
     *
     * @throws LogicException If an array value is encountered in the form
     */
    protected function save(): void
    {
        foreach ($this->getValues() as $element => $value) {
            [$section, $key] = Str::symmetricSplit($element, $this->sectionKeyDelimiter, 2);
            if ($key === null) {
                continue;
            }

            if (is_array($value)) {
                throw new LogicException(sprintf('Cannot save element "%s": array values are not supported', $element));
            }

            $configSection = $this->config->getSection($section);
            if (Str::isEmpty($value)) {
                unset($configSection[$key]);
            } else {
                $configSection[$key] = $value;
            }

            if ($configSection->isEmpty()) {
                $this->config->removeSection($section);
            } else {
                $this->config->setSection($section, $configSection);
            }
        }

        $this->config->saveIni();
    }

    /**
     * Handle the form submission
     *
     * If the form submission is successful, the configuration is saved to disk.
     * If an error occurs, the form is re-rendered with the error message and the
     * raw INI configuration. The original exception is re-thrown.
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        try {
            $this->save();
        } catch (Exception $e) {
            $content = $this->getContent();
            array_unshift($content, new DisplayFormElement(static::createConfigurationErrorHint($e, $this->config)));
            $this->setContent($content);

            throw $e;
        }
    }

    /**
     * Add the submit button to the form
     *
     * Called automatically during assembly. Subclasses may override this to add
     * additional required elements but must call the parent implementation.
     *
     * @return void
     */
    protected function addRequiredElements(): void
    {
        $this->addElement('submit', static::SUBMIT_BUTTON_NAME, [
            'label' => $this->translate('Store'),
            'ignore' => true,
        ]);
    }
}
