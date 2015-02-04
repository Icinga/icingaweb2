<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
        $this->view->dashboard = SearchDashboard::search($this->params->get('q'));

        // NOTE: This renders the dashboard twice. Remove this once we can catch exceptions thrown in view scripts.
        $this->view->dashboard->render();
    }

    public function hintAction()
    {
    }
}
