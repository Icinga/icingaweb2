<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Menu;
use Icinga\Web\Navigation\Mobile\MobileConfigMenu;
use Icinga\Web\Navigation\Mobile\MobileMenu;
use ipl\Web\Compat\CompatController;

/**
 * Create complex layout parts
 */
class LayoutController extends CompatController
{
    /**
     * Render the menu
     */
    public function menuAction()
    {
        $this->setAutorefreshInterval(15);
        $this->_helper->layout()->disableLayout();
        $this->view->menuRenderer = (new Menu())->getRenderer();
    }

    public function mobileConfigMenuAction()
    {
        $this->setAutorefreshInterval(15);
        $this->_helper->layout()->disableLayout();
        $this->view->compact = true;
        $this->getDocument()->addHtml(new MobileConfigMenu());
    }

    public function mobileMenuAction()
    {
        $this->setAutorefreshInterval(15);
        $this->_helper->layout()->disableLayout();
        $this->view->compact = true;
        $this->getDocument()->addHtml(new MobileMenu());
    }

    public function announcementsAction()
    {
        $this->_helper->layout()->disableLayout();
    }
}
