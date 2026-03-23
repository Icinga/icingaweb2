<?php
/* Icinga Web 2 | (c) 2026 Icinga GmbH | GPLv2+ */

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
    /**
     * A list of elements that should not be saved to the configuration
     *
     * @var string[]
     */
    protected array $ignoredElements = [];

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
        foreach ($this->getElements() as $element) {
            [$section, $key] = $this->getIniKeyFromName($element->getName());
            if ($section === null || $key === null) {
                continue;
            }
            $value = $this->getPopulatedValue($element->getName()) ?? $this->config->get($section, $key);
            $this->populate([
                $element->getName() => $value,
            ]);
        }
    }

    /**
     * Get the section and key from the element name.
     * If `$this->section` it is used as the section name, with the key being the element name.
     * Otherwise, the section is determined from the element name.
     *
     * @param string $name The element name
     *
     * @return string[]|null
     */
    protected function getIniKeyFromName(string $name): ?array
    {
        if ($this->section !== null) {
            return [$this->section, $name];
        }

        $parts = explode('__', $name, 2);
        if (count($parts) !== 2) {
            return null;
        }

        return $parts;
    }

    /**
     * Get the value of a configuration key from an element name
     *
     * @param string $name The element name
     * @param mixed $default The default value to return if the element does not exist or the value is empty
     *
     * @return mixed The value of the configuration key or the default value
     */
    public function getConfigValue(string $name, mixed $default = null): mixed
    {
        if (! $this->hasElement($name)) {
            return $default;
        }

        if (($value = $this->getPopulatedValue($name)) !== null) {
            return $value;
        }

        [$section, $key] = $this->getIniKeyFromName($name);
        if (! $this->config->hasSection($section)) {
            return $default;
        }

        return $this->config->get($section, $key, $default);
    }

    /**
     * Persist the current configuration to disk
     *
     * If an error occurs, the form will be re-rendered with the error message and the raw INI configuration.
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
            $value = $this->getConfigValue($element->getName());

            $configSection = $this->config->getSection($section);
            if (empty($value)) {
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
}
