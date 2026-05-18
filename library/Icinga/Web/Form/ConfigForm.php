<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form;

use Exception;
use Icinga\Application\Config;
use Icinga\Web\Widget\ShowConfiguration;
use ipl\Web\Compat\CompatForm;

/**
 * Form base-class providing standard functionality for configuration forms
 */
class ConfigForm extends CompatForm
{
    /** @var string Name of the submit button element */
    protected const SUBMIT_BUTTON_NAME = 'store';

    /**
     * A list of elements that should not be saved to the configuration
     *
     * @var string[]
     */
    protected array $ignoredElements = [self::SUBMIT_BUTTON_NAME];

    public function __construct(
        protected Config $config,
    ) {
    }

    public function ensureAssembled(): static
    {
        if (! $this->hasBeenAssembled) {
            parent::ensureAssembled();
            $this->populateFromConfig();
        }

        return $this;
    }

    /**
     * Populate the form elements from the configuration
     *
     * @return void
     */
    protected function populateFromConfig(): void
    {
        $populate = [];
        foreach ($this->getElements() as $element) {
            [$section, $key] = $this->getIniKeyFromName($element->getName());
            if ($section === null || $key === null) {
                continue;
            }
            $value = $this->getPopulatedValue($element->getName()) ?? $this->config->get($section, $key);
            if ($value === null) {
                continue;
            }
            $populate[$element->getName()] = $value;
        }
        $this->populate($populate);
    }

    /**
     * Get the section and key from the element name
     *
     * @param string $name The element name
     *
     * @return string[]|null
     */
    protected function getIniKeyFromName(string $name): ?array
    {
        $parts = explode('__', $name, 2);

        if (count($parts) !== 2) {
            return [null, null];
        }

        return $parts;
    }

    /**
     * Get the value of a configuration key from an element name
     *
     * @param string $name The element name
     * @param mixed $default The default value to return if the config entry does not exist
     *
     * @return mixed The value of the configuration key or the default value
     */
    public function getConfigValue(string $name, mixed $default = null): mixed
    {
        [$section, $key] = $this->getIniKeyFromName($name);
        if ($section === null || $key === null) {
            return $default;
        }

        return $this->config->get($section, $key, $default);
    }

    /**
     * Persist the current configuration to disk
     *
     * If an error occurs, the form will be re-rendered with the error message
     * and the raw INI configuration.
     */
    protected function save(): void
    {
        foreach ($this->getElements() as $element) {
            if (in_array($element->getName(), $this->ignoredElements)) {
                continue;
            }
            [$section, $key] = $this->getIniKeyFromName($element->getName());
            if ($section === null || $key === null) {
                continue;
            }

            $value = $this->getPopulatedValue($element->getName());
            $configSection = $this->config->getSection($section);
            if ((string) $value === '') {
                unset($configSection[$key]);
            } else {
                $configSection->$key = $value;
            }

            if ($configSection->isEmpty()) {
                $this->config->removeSection($section);
            } else {
                $this->config->setSection($section, $configSection);
            }
        }

        $this->config->saveIni();
    }

    protected function onSuccess(): void
    {
        try {
            $this->save();
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
     * Add the store button to the form
     *
     * @return void
     */
    protected function addButtonElements(): void
    {
        $this->addElement('submit', static::SUBMIT_BUTTON_NAME, [
            'label' => $this->translate('Store'),
        ]);
    }
}
