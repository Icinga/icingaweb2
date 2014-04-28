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
    protected $id;

    /**
     * Item title
     *
     * Used for sorting when priority is unset or equal to other items
     *
     * @type string
     */
    protected $title;

    /**
     * Item priority
     *
     * Used for sorting
     *
     * @type int
     */
    protected $priority = 100;

    /**
     * Item url
     *
     * @type string
     */
    protected $url;

    /**
     * Item icon path
     *
     * @type string
     */
    protected $icon;

    /**
     * Item icon class
     *
     * @type string
     */
    protected $iconClass;

    /**
     * Item's children
     *
     * @type array
     */
    protected $children = array();

    /**
     * HTML anchor tag attributes
     *
     * @var array
     */
    protected $attribs = array();

    /**
     * Create a new MenuItem
     *
     * @param   int         $id
     * @param   object      $config
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
     * @param   string  $id
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
     * @param   string  $title
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
     * @return  string
     */
    public function getTitle()
    {
        return $this->title ? $this->title : $this->id;
    }

    /**
     * Setter for priority
     *
     * @param   int     $priority
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
     * @return  int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Setter for URL
     *
     * @param   string  $url
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
     * @return  string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Setter for icon path
     *
     * @param   string  $path
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
     * @return  string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Setter for icon class
     *
     * @param   string  $iconClass
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
     * @return  string
     */
    public function getIconClass()
    {
        return $this->iconClass;
    }

    /**
     * Set the configuration for the item
     *
     * @param   Zend_Config     $config
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
     * Check whether the item has any children
     *
     * @return  bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * Get sorted children
     *
     * @return  array
     *
     * @see     MenuItem::cmpChildren()
     */
    public function getChildren()
    {
        usort($this->children, array($this, 'cmpChildren'));
        return $this->children;
    }

    /**
     * Return whether a given child id exists
     *
     * @param   string  $id
     *
     * @return  bool
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
     *
     * @throws  ProgrammingError
     */
    public function getChild($id)
    {
        if (!$this->hasChild($id)) {
            throw new ProgrammingError(sprintf('Trying to get invalid Menu child "%s"', $id));
        }

        return $this->children[$id];
    }

    /**
     * Set HTML anchor tag attributes
     *
     * @param   array   $attribs
     *
     * @return  self
     */
    public function setAttribs(array $attribs)
    {
        $this->attribs = $attribs;
        return $this;
    }

    /**
     * Get HTML anchor tag attributes
     *
     * @return  array
     */
    public function getAttribs()
    {
        return $this->attribs;
    }

    /**
     * Compare children based on priority and title
     *
     * @param   MenuItem    $a
     * @param   MenuItem    $b
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
