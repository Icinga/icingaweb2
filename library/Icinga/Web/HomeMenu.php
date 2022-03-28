<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web;

use Icinga\Model\Home;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Navigation\DashboardHomeItem;
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
            $dashboardHome = new DashboardHomeItem($home->name, [
                'uuid'     => $home->id,
                'label'    => t($home->label),
                'priority' => $home->priority,
                'type'     => $home->type,
            ]);

            $dashboardItem->addChild($dashboardHome);
        }
    }

    /**
     * Load dashboard homes form the navigation menu
     *
     * @return DashboardHome[]
     */
    public function loadHomes()
    {
        $homes = [];
        foreach ($this->getItem('dashboard')->getChildren() as $child) {
            if (! $child instanceof DashboardHomeItem) {
                continue;
            }

            $home = DashboardHome::create($child);
            $home->setTitle($child->getLabel());

            $homes[$child->getName()] = $home;
        }

        return $homes;
    }
}
