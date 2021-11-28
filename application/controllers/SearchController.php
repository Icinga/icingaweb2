<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\DBUser;
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
        $user = $this->Auth()->getUser();
        $searchDashboard->getActiveHome()->setAuthUser((new DBUser($user->getUsername()))->extractFrom($user));

        $this->controls->setTabs($searchDashboard->getTabs());
        $this->content = $searchDashboard->search($this->getParam('q'));
    }

    public function hintAction()
    {
    }
}
