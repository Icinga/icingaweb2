<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Web\Navigation\NavigationItem;

class UserNavigationItemRenderer extends NavigationItemRenderer
{
    public function getAvatar()
    {
        // Temporarily disabled as of layout issues. Should be fixed once
        // we have avatars
        return '';

        return '<img class="pull-left user-avatar"
                     src="/icingaweb2/static/gravatar?email=icinga%40localhost"
                     alt="Avatar"
                     aria-hidden="true">';
    }

    public function render(NavigationItem $item = null)
    {
        return '<div class="clearfix">' . $this->getAvatar() . parent::render($item) . '</div>';
    }
}
