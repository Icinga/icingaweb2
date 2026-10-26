<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form;

use Exception;
use Icinga\Application\Config;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Stdlib\Str;
use ipl\Validator\CallbackValidator;
use ipl\Web\Compat\DisplayFormElement;
use LogicException;
use stdClass;

/**
 * Base class for configuration forms that manage a single INI section
 *
 * Extends {@see ConfigForm} with support for creating, renaming, and deleting
 * named sections. Element names map directly to keys within the section rather
 * than encoding the section via the {@see ConfigForm::$sectionKeyDelimiter}
 *
 * Emits {@see self::ON_DELETE} after a section is deleted and {@see self::ON_RENAME}
 * after a section is renamed.
 */
class ConfigSectionForm extends ConfigForm
{
    /** @var string Name of the delete button element */
    protected const DELETE_BUTTON_NAME = 'delete';

    /** @var string Name of the element containing the section name */
    protected const NAME_ELEMENT_NAME = 'name';

    /** @var string Event emitted when the form has successfully deleted a configuration section */
    public const ON_DELETE = 'delete';

    /** @var string Event emitted when the form has successfully renamed a configuration section */
    public const ON_RENAME = 'rename';

    /** @var bool Whether the form is used for creating a new configuration section */
    protected bool $isCreateForm = false;

    /** @var bool Whether the form allows deletion of the configuration section */
    protected bool $allowDeletion = true;

    /** @var bool Whether the form allows renaming of the configuration section */
    protected bool $allowRename = true;

    /**
     * Create a new configuration form for ini-based configuration files
     *
     * @param Config $config The ini file configuration object to use for the form
     * @param ?string $section The name of the section to manage, or null to allow
     *   the creation of a new section
     */
    public function __construct(
        Config $config,
        protected ?string $section = null,
    ) {
        parent::__construct($config);
        $this->isCreateForm = $section === null;
    }

    /**
     * Populate the form from the configuration and sets the section name element if required
     *
     * @return void
     */
    protected function populateFromConfig(): void
    {
        $populate = [];

        if ($this->allowRename()) {
            $populate[static::NAME_ELEMENT_NAME] = $this->getPopulatedValue(static::NAME_ELEMENT_NAME, $this->section);
        }

        if ($this->section !== null) {
            foreach ($this->getElements() as $element) {
                $name = $element->getName();
                $value = $this->getPopulatedValue($name) ?? $this->config->get($this->section, $name);
                if ($value !== null) {
                    $populate[$name] = $value;
                }
            }
        }

        $this->populate($populate);
    }

    public function registerElement(FormElement $element): static
    {
        if ($this->section !== null) {
            $name = $element->getName();
            if ($name !== null) {
                $this->originalValues[$name] = $element->getValue();

                $sentinel = new stdClass();
                if ($name === static::NAME_ELEMENT_NAME) {
                    $configValue = $this->section;
                } else {
                    $configValue = $this->config->getSection($this->section)
                        ->get($name, $this->originalValues[$name] ?? $sentinel);
                }

                if ($configValue !== $sentinel) {
                    $populatedValues = $this->getPopulatedValues($name);
                    $this->clearPopulatedValue($name);
                    $this->populate([$name => $configValue]);
                    foreach ($populatedValues as $value) {
                        $this->populate([$name => $value]);
                    }
                }
            }
        }

        return parent::registerElement($element);
    }

    public function isValidEvent($event): bool
    {
        if ($event === static::ON_DELETE || $event === static::ON_RENAME) {
            return true;
        }

        return parent::isValidEvent($event);
    }

    /**
     * Check if the form is valid
     *
     * If the form is submitted to delete a section, only the CSRF token is
     * required. This is done to allow for deletion of sections that contain
     * invalid configuration. If the CSRF token is not present, the form is
     * considered valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->hasBeenSubmitted() && $this->shouldDelete()) {
            if (! $this->hasElement('CSRFToken')) {
                return true;
            }

            return $this->getElement('CSRFToken')->isValid();
        }

        return parent::isValid();
    }

    /**
     * Whether the form is used for creating a new configuration section
     *
     * @return bool
     */
    public function isCreateForm(): bool
    {
        return $this->isCreateForm;
    }

    /**
     * Set whether the form allows deletion of the configuration section
     *
     * @param bool $allowDeletion
     *
     * @return $this
     *
     * @throws LogicException If the form has already been assembled or if the form
     *   is a creation form
     */
    public function setAllowDeletion(bool $allowDeletion = true): static
    {
        if ($this->isCreateForm()) {
            throw new LogicException('Can never delete a new configuration section');
        }

        if ($this->hasBeenAssembled) {
            throw new LogicException('Form has already been assembled');
        }

        $this->allowDeletion = $allowDeletion;

        return $this;
    }

    /**
     * Whether the form is allowed to delete the configuration section
     *
     * Note: Creation forms are never allowed to be deleted.
     *
     * @return bool
     */
    public function allowDeletion(): bool
    {
        return ! $this->isCreateForm() && $this->allowDeletion;
    }

    /**
     * Set the ability to rename the configuration section
     *
     * @param bool $allowRename Whether the form is allowed to rename the configuration section
     *
     * @return $this
     *
     * @throws LogicException If the form has already been assembled or if the form
     *   is a creation form
     */
    public function setAllowRename(bool $allowRename = true): static
    {
        if ($this->isCreateForm()) {
            throw new LogicException('Can never rename a new configuration section');
        }

        if ($this->hasBeenAssembled) {
            throw new LogicException('Form has already been assembled');
        }

        $this->allowRename = $allowRename;

        return $this;
    }

    /**
     * Whether the form is allowed to rename the configuration section
     *
     * Note: Creation forms are never allowed to be rename forms.
     *
     * @return bool
     */
    public function allowRename(): bool
    {
        return ! $this->isCreateForm() && $this->allowRename;
    }

    /**
     * Handle the deletion of the configuration section
     *
     * This method is called when the delete button is pressed.
     * It deletes the underlying section regardless of whether form validation passed.
     * This is done to allow for deletion of sections that contain invalid configuration.
     *
     * @return void
     */
    protected function handleDelete(): void
    {
        try {
            $this->config->removeSection($this->section);
            $this->config->saveIni();
            $this->emit(static::ON_DELETE, [$this]);
        } catch (Exception $e) {
            $content = $this->getContent();
            array_unshift($content, new DisplayFormElement(static::createConfigurationErrorHint($e, $this->config)));
            $this->setContent($content);

            throw $e;
        }
    }

    /**
     * Handle the renaming of the configuration section
     *
     * This method is called when the rename button is pressed.
     * It renames the underlying section and updates the section name in the form.
     *
     * @return void
     */
    protected function handleRename(): void
    {
        $oldName = $this->section;
        $newName = $this->getPopulatedValue(static::NAME_ELEMENT_NAME);
        $this->config->setSection($newName, $this->config->getSection($oldName));
        $this->config->removeSection($oldName);
        $this->section = $newName;
        parent::onSuccess();
        $this->emit(static::ON_RENAME, [$this, $oldName, $this->section]);
    }

    /**
     * Check if the delete button has been pressed and the section should be deleted
     *
     * @return bool
     */
    public function shouldDelete(): bool
    {
        if (! $this->hasDeleteButton()) {
            return false;
        }

        $deleteButton = $this->getElement(static::DELETE_BUTTON_NAME);
        if (! ($deleteButton instanceof FormSubmitElement)) {
            return false;
        }

        return $deleteButton->hasBeenPressed();
    }

    /**
     * Check if the form has a delete button
     *
     * @return bool
     */
    public function hasDeleteButton(): bool
    {
        return $this->hasElement(static::DELETE_BUTTON_NAME);
    }

    /**
     * Add the section name element to the form
     *
     * This element is used to create a new configuration section with the given
     * name. The added element automatically validates that the name is unique
     * within the configuration.
     *
     * @param array $params Additional parameters to pass to the element constructor
     *
     * @return void
     */
    protected function addSectionNameElement(array $params = []): void
    {
        if (! $this->isCreateForm() && ! $this->allowRename()) {
            return;
        }

        if ($this->hasElement(static::NAME_ELEMENT_NAME)) {
            return;
        }

        $params['required'] = true;
        $params['ignore'] = true;
        $params['label'] ??= $this->translate('Name');
        $params['validators'][] = new CallbackValidator(function ($value, CallbackValidator $validator) {
            if ($value === $this->section) {
                return true;
            }

            if (Str::isEmpty($value)) {
                $validator->addMessage($this->translate('Please enter a name'));

                return false;
            }

            if ($this->config->hasSection($value)) {
                $validator->addMessage($this->translate('An entry with this name already exists'));

                return false;
            }

            return true;
        });

        $this->addElement('text', static::NAME_ELEMENT_NAME, $params);
    }

    protected function onSuccess(): void
    {
        if ($this->isCreateForm()) {
            $this->section = $this->getValue(static::NAME_ELEMENT_NAME);
            parent::onSuccess();
        } elseif ($this->shouldDelete()) {
            $this->handleDelete();
        } elseif ($this->shouldRename()) {
            $this->handleRename();
        } else {
            parent::onSuccess();
        }
    }

    protected function addRequiredElements(): void
    {
        parent::addRequiredElements();

        if ($this->allowDeletion()) {
            $deleteButton = $this->createElement(
                'submit',
                static::DELETE_BUTTON_NAME,
                [
                    'label' => $this->translate('Delete'),
                    'formnovalidate' => true,
                    'ignore' => true,
                ],
            );
            $this->registerElement($deleteButton);
            /** @var BaseHtmlElement $wrapper */
            $wrapper = $this->getElement(static::SUBMIT_BUTTON_NAME)->getWrapper();
            $wrapper->prepend($deleteButton);
        }

        if (($this->isCreateForm() || $this->allowRename()) && ! $this->hasElement(static::NAME_ELEMENT_NAME)) {
            $this->addSectionNameElement();

            $content = $this->getContent();
            $index = null;
            foreach ($content as $key => $element) {
                /** @var FormElement $element */
                if ($element->getName() === static::NAME_ELEMENT_NAME) {
                    $index = $key;

                    break;
                }
            }

            if ($index === null) {
                throw new LogicException('Could not find section name element');
            }

            $element = $content[$index];
            unset($content[$index]);
            array_unshift($content, $element);
            $this->setContent($content);
        }
    }

    public function hasBeenSubmitted()
    {
        if (! $this->hasBeenSent()) {
            return false;
        }

        if ($this->shouldDelete()) {
            return true;
        }

        return parent::hasBeenSubmitted();
    }

    /**
     * Check if the form should rename the section for this request
     *
     * @return bool
     */
    protected function shouldRename(): bool
    {
        if (! $this->allowRename()) {
            return false;
        }

        return $this->section !== $this->getPopulatedValue(static::NAME_ELEMENT_NAME);
    }

    /**
     * Save the configuration to disk
     *
     * This method is called after the form has been submitted and validated.
     * It saves the configuration to disk using the {@see Config} object.
     * Empty sections are explicitly not removed, because of cases where the
     * mere presence of a section is sufficient to indicate that a resource
     * exists and the default values are sufficient and should be used.
     *
     * @return void
     */
    protected function save(): void
    {
        $configSection = $this->config->getSection($this->section);
        foreach ($this->getValues() as $element => $value) {
            if (is_array($value)) {
                throw new LogicException(sprintf('Cannot save element "%s": array values are not supported', $element));
            }

            if ($element === static::NAME_ELEMENT_NAME) {
                continue;
            }

            if (Str::isEmpty($value)) {
                unset($configSection[$element]);
            } else {
                $configSection[$element] = $value;
            }
        }

        $this->config->setSection($this->section, $configSection);
        $this->config->saveIni();
    }
}
