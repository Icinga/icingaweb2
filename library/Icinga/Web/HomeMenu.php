<?php

namespace Icinga\Web;

use Icinga\Model\Home;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Stdlib\Filter;

/**
 * Entrypoint of the dashboard homes
 */
class HomeMenu extends Menu
{
    public function __construct()
    {
        parent::__construct();

        $this->initHome();
    }

    public function initHome()
    {
        $user = Dashboard::getUser();
        $dashboardItem = $this->getItem('dashboard');

        $homes = Home::on(Dashboard::getConn());
        $homes->filter(Filter::equal('username', $user->getUsername()));

        foreach ($homes as $home) {
            $dashboardHome = new DashboardHome($home->name, [
                'uuid'      => $home->id,
                'label'     => t($home->label),
                'priority'  => $home->priority,
                'type'      => $home->type,
            ]);

            $dashboardItem->addChild($dashboardHome);
        }
    }
}
