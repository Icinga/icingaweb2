<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form;

use Exception;
use Icinga\Application\Config;
use Icinga\Web\Widget\ShowConfiguration;
use ipl\Stdlib\Str;
use ipl\Web\Compat\CompatForm;

/**
 * Form base-class providing standard functionality for configuration forms
 */
class ConfigForm extends CompatForm
{
    /** @var string Name of the submit button element */
    protected const SUBMIT_BUTTON_NAME = 'store';

    /** @var string Delimiter used to separate the section and key in the element name */
    protected string $sectionKeyDelimiter = '__';

    /**
     * A list of elements that should not be saved to the configuration
     *
     * @var string[]
     */
    protected array $ignoredElements = [];

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
            if (($parts = $this->getIniKeyFromName($element->getName())) === null) {
                continue;
            }
            [$section, $key] = $parts;
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
     * @return ?array<string, string>
     */
    protected function getIniKeyFromName(string $name): ?array
    {
        $parts = explode($this->sectionKeyDelimiter, $name, 2);

        return count($parts) === 2 ? $parts : null;
    }

    /**
     * Get the value of a configuration key from an element name
     *
     * @param string $name The element name
     * @param mixed $default The default value to return if the config entry does not exist
     *
     * @return mixed The value of the configuration key or the default value
     */
    protected function getConfigValue(string $name, mixed $default = null): mixed
    {
        if (($parts = $this->getIniKeyFromName($name)) === null) {
            return $default;
        }
        [$section, $key] = $parts;

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
            if (($parts = $this->getIniKeyFromName($element->getName())) === null) {
                continue;
            }
            [$section, $key] = $parts;

            $value = $this->getPopulatedValue($element->getName());
            $configSection = $this->config->getSection($section);
            if (Str::isEmpty($value)) {
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
