<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller;
use Icinga\Web\Widget\Dashboard;

/**
 * Dashboards
 */
class DashboardsController extends Controller
{
    /**
     * Display the dashboard with the pane set in the "pane" request parameter
     *
     * If no pane is given or the given one doesn't exist, the default pane is displayed
     */
    public function indexAction()
    {
        $dashboard = new Dashboard();
        $dashboard->setUser($this->Auth()->getUser());
        $dashboard->load();

        $this->view->title = 'Dashboard';
        $this->view->tabs = $dashboard->getTabs();

        if ($dashboard->hasPanes()) {
            if (($pane = $this->params->get('pane')) !== null) {
                $dashboard->activate($pane);
            }

            $this->view->dashboard = $dashboard;
            $this->view->title = $dashboard->getActivePane()->getTitle() . ' :: Dashboard';
        }
    }
}
