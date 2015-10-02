<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Web\Controller\ActionController;

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
        $this->view->menuRenderer = Icinga::app()->getMenu()->getRenderer();
    }
}
