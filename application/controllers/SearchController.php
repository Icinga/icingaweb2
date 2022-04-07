<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Widget\SearchDashboard;
use ipl\Web\Compat\CompatController;

/**
 * Search controller
 */
class SearchController extends CompatController
{
    public function indexAction()
    {
        $searchDashboard = new SearchDashboard();
        $searchDashboard->setUser($this->Auth()->getUser());

        $this->controls->setTabs($searchDashboard->getTabs());
        $this->addContent($searchDashboard->search($this->getParam('q')));
    }

    public function hintAction()
    {
    }
}
