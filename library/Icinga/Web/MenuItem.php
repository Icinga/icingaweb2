<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Exception\ProgrammingError;

class MenuItem
{
    /**
     * Item id
     *
     * @type string
     */
    private $id;

    /**
     * Item title
     *
     * Used for sorting when priority is unset or equal to other items
     *
     * @type string
     */
    private $title;

    /**
     * Item priority
     *
     * Used for sorting
     *
     * @type int
     */
    private $priority = 100;

    /**
     * Item url
     *
     * @type string
     */
    private $url;

    /**
     * Item icon path
     *
     * @type string
     */
    private $icon;

    /**
     * Item icon class
     *
     * @type string
     */
    private $iconClass;

    /**
     * Item's children
     *
     * @type array
     */
    private $children = array();


    /**
     * Create a new MenuItem
     *
     * @param int       $id
     * @param object    $config
     */
    public function __construct($id, $config = null)
    {
        $this->id = $id;
        if ($config !== null) {
            $this->setConfig($config);
        }
    }

    /**
     * Setter for id
     *
     * @param   string $id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Getter for id
     *
     * @return  string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for title
     *
     * @param   string $title
     *
     * @return  self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }


    /**
     * Getter for title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title ? $this->title : $this->id;
    }

    /**
     * Setter for priority
     *
     * @param   int $priority
     *
     * @return  self
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;
        return $this;
    }

    /**
     * Getter for priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Setter for URL
     *
     * @param   string $url
     *
     * @return  self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Getter for URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Setter for icon path
     *
     * @param   string $path
     *
     * @return  self
     */
    public function setIcon($path)
    {
        $this->icon = $path;
        return $this;
    }

    /**
     * Getter for icon path
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Setter for icon class
     *
     * @param   string $iconClass
     *
     * @return  self
     */
    public function setIconClass($iconClass)
    {
        $this->iconClass = $iconClass;
        return $this;
    }

    /**
     * Getter for icon class
     *
     * @return string
     */
    public function getIconClass()
    {
        return $this->iconClass;
    }

    /**
     * Set the configuration for the item
     *
     * @param object $config
     */
    public function setConfig($config)
    {
        foreach ($config as $key => $value) {
            $method = 'set' . implode('', array_map('ucfirst', explode('_', strtolower($key))));
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * Add a child to MenuItem
     *
     * @param   string  $id
     * @param   object  $itemConfig
     *
     * @return  self
     */
    public function addChild($id, $itemConfig = null)
    {
        if (false === ($pos = strpos($id, '.'))) {
            // Root item
            $menuItem = new self($id, $itemConfig);
            $this->children[$id] = $menuItem;
        } else {
            // Submenu item
            list($parentId, $id) = explode('.', $id, 2);
            if ($this->hasChild($parentId)) {
                $parent = $this->getChild($parentId);
            } else {
                $parent = $this->addChild($parentId);
            }
            $menuItem = $parent->addChild($id, $itemConfig);
        }
        return $menuItem;
    }

    /**
     * Check whether the item has children
     *
     * @return  bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * Get children sorted
     *
     * @return  array
     * @see     cmpChildren()
     */
    public function getChildren()
    {
        usort($this->children, array($this, 'cmpChildren'));
        return $this->children;
    }

    /**
     * Whether a given child id exists
     *
     * @param   string  $id
     *
     * @return  self|$default
     */
    public function hasChild($id)
    {
        return array_key_exists($id, $this->children);
    }

    /**
     * Get child by its id
     *
     * @param   string  $id
     * @param   mixed   $default
     *
     * @return  MenuItem
     * @throws  ProgrammingError
     */
    public function getChild($id)
    {
        if ($this->hasChild($id)) {
            return $this->children[$id];
        }
        throw new ProgrammingError(sprintf('Trying to get invalid Menu child "%s"', $id));
    }


    /**
     * Compare children based on priority and title
     *
     * @param   MenuItem $a
     * @param   MenuItem $b
     *
     * @return  int
     */
    protected function cmpChildren($a, $b)
    {
        if ($a->priority === $b->priority) {
            return ($a->getTitle() > $b->getTitle()) ? 1 : -1;
        }
        return ($a->priority > $b->priority) ? 1 : -1;
    }
}
