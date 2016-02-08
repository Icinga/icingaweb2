<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Web\Widget;
use Icinga\Web\Widget\SearchDashboard;

/**
 * Search controller
 */
class SearchController extends ActionController
{
    public function indexAction()
    {
        $searchDashboard = new SearchDashboard();
        $searchDashboard->setUser($this->Auth()->getUser());
        $this->view->dashboard = $searchDashboard->search($this->params->get('q'));

        // NOTE: This renders the dashboard twice. Remove this once we can catch exceptions thrown in view scripts.
        $this->view->dashboard->render();
    }

    public function hintAction()
    {
    }
}
