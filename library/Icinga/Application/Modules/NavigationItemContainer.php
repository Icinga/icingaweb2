<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Modules;

use Icinga\Exception\ProgrammingError;

/**
 * Container for module navigation items
 */
abstract class NavigationItemContainer
{
    /**
     * This navigation item's name
     *
     * @var string
     */
    protected $name;

    /**
     * This navigation item's properties
     *
     * @var array
     */
    protected $properties;

    /**
     * Create a new NavigationItemContainer
     *
     * @param   string  $name
     * @param   array   $properties
     */
    public function __construct($name, array $properties = array())
    {
        $this->name = $name;
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
            return call_user_func(array($this, $name), $this, $arguments);
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
