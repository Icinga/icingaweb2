<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Web\Navigation\NavigationItem;

class UserNavigationItemRenderer extends NavigationItemRenderer
{
    public function getAvatar()
    {
        return '<img class="pull-left user-avatar"
                     src="/icingaweb2/static/gravatar?email=icinga%40localhost"
                     alt="Avatar"
                     aria-hidden="true">';
    }

    public function render(NavigationItem $item = null)
    {
        return '<div class="user-nav-item clearfix">' . $this->getAvatar() . parent::render($item) . '</div>';
    }
}
