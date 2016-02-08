<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Modules;

/**
 * Container for module menu items
 */
class MenuItemContainer extends NavigationItemContainer
{
    /**
     * This menu item's children
     *
     * @var MenuItemContainer[]
     */
    protected $children;

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
        return $this->children ?: array();
    }

    /**
     * Add a new sub menu
     *
     * @param   string  $name
     * @param   array   $properties
     *
     * @return  MenuItemContainer   The newly added sub menu
     */
    public function add($name, array $properties = array())
    {
        $child = new MenuItemContainer($name, $properties);
        $this->children[] = $child;
        return $child;
    }
}
