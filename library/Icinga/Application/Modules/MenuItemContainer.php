<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Modules;

/**
 * Container for module menus
 */
class MenuItemContainer
{
    /**
     * This menu item's name
     *
     * @var string
     */
    protected $name;

    /**
     * This menu item's properties
     *
     * @var array
     */
    protected $properties;

    /**
     * This menu item's children
     *
     * @var MenuItemContainer[]
     */
    protected $children;

    /**
     * Create a new MenuItemContainer
     *
     * @param   string  $name
     * @param   array   $properties
     */
    public function __construct($name, array $properties = null)
    {
        $this->name = $name;
        $this->children = array();
        $this->properties = $properties;
    }

    /**
     * Set this menu item's name
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return this menu item's name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set this menu item's properties
     *
     * @param   array   $properties
     *
     * @return  $this
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Return this menu item's properties
     *
     * @return  array
     */
    public function getProperties()
    {
        return $this->properties ?: array();
    }

    /**
     * Set this menu item's children
     *
     * @param   MenuItemContainer[]     $children
     *
     * @return  $this
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     * Return this menu item's children
     *
     * @return  array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add a new child
     *
     * @param   string  $name
     * @param   array   $properties
     *
     * @return  MenuItemContainer   The newly added menu item
     */
    public function add($name, array $properties = null)
    {
        $child = new static($name, $properties);
        $this->children[] = $child;
        return $child;
    }

    /**
     * Allow dynamic setters and getters for properties
     *
     * @param   string  $name
     * @param   array   $arguments
     *
     * @return  mixed
     *
     * @throws  ProgrammingError    In case the called method is not supported
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_method_array($name, $this, $arguments);
        }

        $type = substr($name, 0, 3);
        if ($type !== 'set' && $type !== 'get') {
            throw new ProgrammingError(
                'Dynamic method %s is not supported. Only getters (get*) and setters (set*) are.',
                $name
            );
        }

        $propertyName = strtolower(join('_', preg_split('~(?=[A-Z])~', lcfirst(substr($name, 3)))));
        if ($type === 'set') {
            $this->properties[$propertyName] = $arguments[0];
            return $this;
        } else { // $type === 'get'
            return array_key_exists($propertyName, $this->properties) ? $this->properties[$propertyName] : null;
        }
    }
}
