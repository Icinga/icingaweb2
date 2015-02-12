<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Menu;

use Icinga\Web\Menu;
use Icinga\Web\Url;

/**
 * A menu item with a link that surpasses the regular navigation link behavior
 */
class ForeignMenuItemRenderer implements MenuItemRenderer {

    public function render(Menu $menu)
    {
        return sprintf(
            '<a href="%s" target="_self">%s%s<span></span></a>',
            $menu->getUrl() ?: '#',
            $menu->getIcon() ? '<img aria-hidden="true" src="' . Url::fromPath($menu->getIcon()) . '" class="icon" /> ' : '',
            htmlspecialchars($menu->getTitle())
        );
    }
}
