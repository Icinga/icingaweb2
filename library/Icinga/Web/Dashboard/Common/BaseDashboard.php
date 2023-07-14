<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

/**
 * Base class for all dashboard widget types
 *
 * All Icinga Web dashboard widgets should extend this class
 */
abstract class BaseDashboard
{
    /**
     * Not translatable name of this widget
     *
     * @var string
     */
    protected $name;

    /**
     * The title of this widget
     *
     * @var string
     */
    protected $title;

    /**
     * Unique identifier of this widget
     *
     * @var int|string
     */
    protected $uuid;

    /**
     * The widget's description
     *
     * @var string
     */
    protected $description;

    /**
     * The priority order of this widget
     *
     * @var int
     */
    protected $order = 0;

    /**
     * Name of the owner/creator of this widget
     *
     * @var string
     */
    protected $owner;

    /**
     * A flag whether this widget is currently being loaded
     *
     * @var bool
     */
    protected $active = false;

    /**
     * A flag whether this widget has been disabled (affects only default home)
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * Create a new widget
     *
     * @param string $name
     * @param array $properties
     */
    public function __construct(string $name, array $properties = [])
    {
        $this->name = $name;
        $this->title = $name;

        if (! empty($properties)) {
            $this->setProperties($properties);
        }
    }

    /**
     * Set this widget's unique identifier
     *
     * @param int|string $uuid
     *
     * @return $this
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get this widget's unique identifier
     *
     * @return string|int
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set the name of this widget
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the name of this widget
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the title of this widget
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Returns the title of this widget
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title !== null ? $this->title : $this->getName();
    }

    /**
     * Set the owner of this widget
     *
     * @param string $owner
     *
     * @return $this
     */
    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner of this widget
     *
     * @return ?string
     */
    public function getOwner(): ?string
    {
        return $this->owner;
    }

    /**
     * Get the widget's description
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the widget's description
     *
     * @param ?string $description
     *
     * @return  $this
     */
    public function setDescription(string $description = null): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the priority order of this widget
     *
     * @param int $order
     *
     * @return $this
     */
    public function setPriority(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the priority order of this widget
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->order;
    }

    /**
     * Set properties from the given list (no matching setter) are ignored
     *
     * @param array $data
     *
     * @return $this
     */
    public function setProperties(array $data): self
    {
        foreach ($data as $name => $value) {
            $func = 'set' . ucfirst($name);
            if ($value && method_exists($this, $func)) {
                $this->$func($value);
            }
        }

        return $this;
    }

    /**
     * Set whether this widget should be disabled
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get whether this widget has been disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Set whether this widget is currently being loaded
     *
     * @param bool $active
     *
     * @return $this
     */
    public function setActive(bool $active = true): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get whether this widget is currently being loaded
     *
     * Indicates which dashboard tab is currently open if this widget is a Dashboard Pane type
     * or whether the Dashboard Home is active/focused in the navigation bar
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Get this class's structure as array
     *
     * Stringifies the attrs or set to null if it doesn't have a value, when $stringify is true
     *
     * @param bool $stringify Whether the attributes should be returned unmodified
     *
     * @return array
     */
    public function toArray(bool $stringify = true): array
    {
        return [
            'id'          => $this->getUuid(),
            'name'        => $this->getName(),
            'label'       => $this->getTitle(),
            'owner'       => $this->getOwner(),
            'priority'    => $this->getPriority(),
            'description' => $this->getDescription()
        ];
    }
}
