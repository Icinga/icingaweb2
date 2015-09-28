<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Web\Navigation\NavigationItem;

class LogoutNavigationItemRenderer extends NavigationItemRenderer
{
    public function render(NavigationItem $item = null)
    {
        return parent::render($item);
    }
}
