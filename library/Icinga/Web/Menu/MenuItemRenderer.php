<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;

/**
 * Renders the html content of a single menu item
 */
interface MenuItemRenderer {
    public function render(Menu $menu);
}
