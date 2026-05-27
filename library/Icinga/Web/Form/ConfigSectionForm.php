<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form;

use Exception;
use Icinga\Application\Config;
use Icinga\Web\Widget\ShowConfiguration;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Validator\CallbackValidator;
use LogicException;

/**
 * Base class for configuration forms that manage a single INI section
 *
 * Extends {@see ConfigForm} with support for creating, renaming, and deleting
 * named sections. Element names map directly to keys within the section rather
 * than encoding the section via the {@see ConfigForm} double-underscore convention.
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

    /**
     * A list of elements that should not be saved to the configuration
     *
     * @var string[]
     */
    protected array $ignoredElements = [self::SUBMIT_BUTTON_NAME, self::DELETE_BUTTON_NAME, self::NAME_ELEMENT_NAME];

    /**
     * Whether the form is used for creating a new configuration section
     *
     * @var bool
     */
    protected bool $isCreateForm = false;

    /**
     * Whether the form allows deletion of the configuration section
     *
     * @var bool
     */
    protected bool $allowDeletion = true;

    /**
     * Whether the form allows renaming of the configuration section
     *
     * @var bool
     */
    protected bool $allowRename = true;

    public function __construct(
        Config $config,
        protected ?string $section = null,
    ) {
        parent::__construct($config);

        $this->isCreateForm = $section === null;

        $this->on(static::ON_SENT, $this->onSent(...));
    }

    /**
     * Handle the form data that has been sent
     *
     * @return void
     */
    protected function onSent(): void
    {
        if ($this->shouldDelete()) {
            $this->handleDelete();
            $this->emit(static::ON_DELETE, [$this]);
        }
    }

    protected function populateFromConfig(): void
    {
        if ($this->allowRename()) {
            $this->populate([
                static::NAME_ELEMENT_NAME => $this->getPopulatedValue(static::NAME_ELEMENT_NAME, $this->section),
            ]);
        }

        parent::populateFromConfig();
    }

    public function isValidEvent($event): bool
    {
        if ($event === static::ON_DELETE || $event === static::ON_RENAME) {
            return true;
        }

        return parent::isValidEvent($event);
    }

    public function isCreateForm(): bool
    {
        return $this->isCreateForm;
    }

    /**
     * Set whether the form allows deletion of the configuration section
     *
     * @param bool $allowDeletion
     *
     * @return static
     */
    public function setAllowDeletion(bool $allowDeletion = true): static
    {
        if (! $this->hasBeenAssembled) {
            $this->allowDeletion = $allowDeletion;
        }

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
        if ($this->isCreateForm()) {
            return false;
        }

        return $this->allowDeletion;
    }

    /**
     * Set the ability to rename the configuration section
     *
     * @param bool $allowRename Whether the form is allowed to rename the configuration section
     *
     * @return $this
     */
    public function setAllowRename(bool $allowRename = true): static
    {
        if (! $this->hasBeenAssembled) {
            $this->allowRename = $allowRename;
        }

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
        if ($this->isCreateForm()) {
            return false;
        }

        return $this->allowRename;
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
        } catch (Exception $e) {
            $content = $this->getContent();
            array_unshift(
                $content,
                new ShowConfiguration(
                    $e,
                    $this->config,
                )
            );
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
     *
     * @throws LogicException
     */
    protected function handleRename(): void
    {
        if ($this->section === null) {
            throw new LogicException('Section must be set before renaming a configuration section.');
        }

        $newName = $this->getPopulatedValue(static::NAME_ELEMENT_NAME);
        $this->config->setSection($newName, $this->config->getSection($this->section));
        $this->config->removeSection($this->section);
        $this->section = $newName;
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

        $params['required'] = true;
        $params['label'] ??= $this->translate('Name');
        $params['validators'][] = new CallbackValidator(function ($value, CallbackValidator $validator) {
            if ((string) $value === '') {
                return true;
            }

            if ($value === $this->section) {
                return true;
            }

            if ($this->config->hasSection($value)) {
                $validator->addMessage($this->translate('An entry with this name already exists.'));
                return false;
            }

            return true;
        });

        $this->addElement('text', static::NAME_ELEMENT_NAME, $params);
    }

    protected function getIniKeyFromName(string $name): ?array
    {
        if ($this->section === null) {
            return null;
        }

        return [$this->section, $name];
    }

    protected function onSuccess(): void
    {
        if ($this->isCreateForm()) {
            $this->section = $this->getValue(static::NAME_ELEMENT_NAME);

            if ($this->section === '') {
                throw new LogicException('Section must be set before saving a new configuration section.');
            }
        }

        $oldSection = $this->section;
        $isRename = $this->shouldRename();

        if ($isRename) {
            $this->handleRename();
        }

        parent::onSuccess();

        if ($isRename) {
            $this->emit(static::ON_RENAME, [
                $this,
                $oldSection,
                $this->section,
            ]);
        }
    }

    protected function addButtonElements(): void
    {
        parent::addButtonElements();

        if (! $this->allowDeletion()) {
            return;
        }

        $deleteButton = $this->createElement(
            'submit',
            static::DELETE_BUTTON_NAME,
            [
                'label' => $this->translate('Delete'),
                'formnovalidate' => true,
            ],
        );
        $this->registerElement($deleteButton);
        $this->getElement(static::SUBMIT_BUTTON_NAME)
            ->getWrapper()
            ->prepend($deleteButton);
    }

    /**
     * Check if the form should rename the section for this request
     *
     * @return bool
     */
    private function shouldRename(): bool
    {
        if (! $this->allowRename() || ! $this->hasBeenSubmitted() || ! $this->isValid()) {
            return false;
        }

        return $this->section !== $this->getPopulatedValue(static::NAME_ELEMENT_NAME);
    }
}
