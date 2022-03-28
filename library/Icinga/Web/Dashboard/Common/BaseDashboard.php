<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Common\DataExtractor;

/**
 * Base class for all dashboard widget types
 *
 * All Icinga Web dashboard widgets should extend this class
 */
abstract class BaseDashboard implements DashboardEntry
{
    use DataExtractor;

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
     * Create a new widget
     *
     * @param string $name
     * @param array $properties
     */
    public function __construct($name, array $properties = [])
    {
        $this->name = $name;
        $this->title = $name;

        if (! empty($properties)) {
            $this->fromArray($properties);
        }
    }

    /**
     * Set this widget's unique identifier
     *
     * @param int|string $uuid
     *
     * @return $this
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get this widget's unique identifier
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set the name of this widget
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of this widget
     *
     * @return string
     */
    public function getName()
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
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Returns the title of this widget
     *
     * @return string
     */
    public function getTitle()
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
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner of this widget
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Get the widget's description
     *
     * @return  string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the widget's description
     *
     * @param string $description
     *
     * @return  $this
     */
    public function setDescription($description)
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
    public function setPriority(int $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the priority order of this widget
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->order;
    }

    public function hasEntries()
    {
    }

    public function getEntry($name)
    {
    }

    public function hasEntry($name)
    {
    }

    public function getEntries()
    {
    }

    public function setEntries(array $entries)
    {
    }

    public function addEntry(BaseDashboard $dashboard)
    {
    }

    public function createEntry($name, $url = null)
    {
    }

    public function getEntryKeyTitleArr()
    {
    }

    public function removeEntry($entry)
    {
    }

    public function removeEntries(array $entries = [])
    {
    }

    public function manageEntry($entry, BaseDashboard $origin = null, $updateChildEntries = false)
    {
    }

    public function loadDashboardEntries($name = '')
    {
    }

    public function rewindEntries()
    {
    }
}
