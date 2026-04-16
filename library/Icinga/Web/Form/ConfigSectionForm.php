<?php

namespace Icinga\Web\Form;

use Exception;
use Icinga\Application\Config;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Widget\ShowConfiguration;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Validator\CallbackValidator;

class ConfigSectionForm extends ConfigForm
{
    /** @var string Name of the delete button element */
    protected const DELETE_BUTTON_NAME = 'delete';

    /** @var string Name of the element containing the section name */
    protected const NAME_ELEMENT_NAME = 'name';

    /** @var string Name of the submit button element */
    protected const SUBMIT_BUTTON_NAME = 'store';

    /** @var string Event emitted when the form has successfully deleted a configuration section */
    public const ON_DELETE = 'delete';

    /**
     * A list of elements that should not be saved to the configuration
     *
     * @var string[]
     */
    protected array $ignoredElements = [self::SUBMIT_BUTTON_NAME, self::DELETE_BUTTON_NAME, self::NAME_ELEMENT_NAME];

    /**
     * The configuration to work with
     *
     * @var Config|null
     */
    protected ?Config $config = null;

    /**
     * The section to work with.
     * If not set, the section is determined from the element name.
     *
     * @var string|null
     */
    protected ?string $section = null;

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

    public function __construct()
    {
        $this->on(static::ON_SENT, function () {
            if ($this->shouldDelete()) {
                $this->handleDelete();
                $this->emit(static::ON_DELETE, [$this]);
            }
        });
    }

    public function isValidEvent($event): bool
    {
        // Check for our new event and return true if it is valid
        if ($event === static::ON_DELETE) {
            return true;
        }

        // Call the parent function to still validate all previous added events
        return parent::isValidEvent($event);
    }

    /**
     * Set the configuration to use when populating and saving
     *
     * @param Config $config The configuration to use
     *
     * @return $this
     */
    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set the section to use when populating and saving
     *
     * @param string $section The section to use
     *
     * @return $this
     */
    public function setSection(string $section): static
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Set whether the form is used for creating a new configuration section, with a name that can be chosen by the user
     *
     * @param bool $create
     *
     * @return static
     */
    public function setIsCreateForm(bool $create = true): static
    {
        $this->isCreateForm = $create;

        return $this;
    }

    /**
     * Is the form used for creating a new configuration section
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
     * @return static
     */
    public function setAllowDeletion(bool $allowDeletion = true): static
    {
        $this->allowDeletion = $allowDeletion;

        return $this;
    }

    /**
     * Is the form allowed to delete the configuration section.
     * Note: Creation forms are never allowed to be deleted.
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
     * Handle the deletion of the configuration section
     *
     * @return void
     */
    protected function handleDelete(): void
    {
        if ($this->section === null) {
            throw new ProgrammingError('Section must be set before deleting a configuration section.');
        }

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
     * Add the section name element to the form. This element is used to create a new configuration section with the
     * given name. The added element automatically validates that the name is unique within the configuration.
     *
     * @param array $params Additional parameters to pass to the element constructor
     *
     * @return void
     */
    protected function addSectionNameElement(array $params = []): void
    {
        if (! $this->isCreateForm()) {
            return;
        }

        $params['required'] = true;

        if (! isset($params['label'])) {
            $params['label'] = $this->translate('Name');
        }

        $uniqueValidator = new CallbackValidator(function ($value, CallbackValidator $validator) {
            if (empty($value)) {
                return true;
            }

            if ($this->config->hasSection($value)) {
                $validator->addMessage($this->translate('An entry with this name already exists.'));
                return false;
            }

            return true;
        });

        if (! isset($params['validators'])) {
            $params['validators'] = [];
        }
        $params['validators'][] = $uniqueValidator;

        $this->addElement('text', static::NAME_ELEMENT_NAME, $params);
    }

    /**
     * Get the section and key from the element name.
     *
     * @param string $name The element name
     *
     * @return string[]|null
     */
    protected function getIniKeyFromName(string $name): ?array
    {
        return [$this->section, $name];
    }

    protected function onSuccess(): void
    {
        if ($this->isCreateForm()) {
            $this->section = $this->getValue(static::NAME_ELEMENT_NAME);

            if (empty($this->section)) {
                throw new ProgrammingError('Section must be set before saving a new configuration section.');
            }
        }

        parent::onSuccess();
    }

    /**
     * Add the store and optionally the delete buttons to the form.
     *
     * @return void
     */
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
}
