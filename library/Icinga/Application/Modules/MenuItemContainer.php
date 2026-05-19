<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
        return $this->children ?: [];
    }

    /**
     * Add a new sub menu
     *
     * @param   string  $name
     * @param   array   $properties
     *
     * @return  MenuItemContainer   The newly added sub menu
     */
    public function add($name, array $properties = [])
    {
        $child = new MenuItemContainer($name, $properties);
        $this->children[] = $child;
        return $child;
    }
}
