<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation;

/**
 * Dropdown navigation item
 *
 * @see \Icinga\Web\Navigation\Navigation For a usage example.
 */
class DropdownItem extends NavigationItem
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->children->setLayout(Navigation::LAYOUT_DROPDOWN);
    }
}
