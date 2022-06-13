<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Web\Url;

/**
 * Handle creation, removal and displaying of dashboards, panes and dashlets
 *
 * @deprecated Use {@see DashboardsController} instead
 */
class DashboardController extends ActionController
{
    public function preDispatch()
    {
        $this->redirectNow(Url::fromRequest()->setPath(Dashboard::BASE_ROUTE));
    }
}
