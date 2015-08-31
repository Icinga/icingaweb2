<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Web\Hook;
use Icinga\Web\Menu;
use Icinga\Web\MenuRenderer;
use Icinga\Web\Url;

/**
 * Create complex layout parts
 */
class LayoutController extends ActionController
{
    /**
     * Render the menu
     */
    public function menuAction()
    {
        $this->setAutorefreshInterval(15);
        $this->_helper->layout()->disableLayout();

        $url = Url::fromRequest();
        $menu = new MenuRenderer(Menu::load(), $url->getRelativeUrl());
        $this->view->menuRenderer = $menu->useCustomRenderer();
    }
}
