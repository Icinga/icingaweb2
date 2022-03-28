<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use ipl\Web\Url;

class DashboardHomeItem extends NavigationItem
{
    /**
     * Init this dashboard home
     *
     * Doesn't set the url of this dashboard home if it's the default one
     * to prevent from being rendered as dropdown in the navigation bar
     *
     * @return void
     */
    public function init()
    {
        if ($this->getName() !== DashboardHome::DEFAULT_HOME) {
            $this->setUrl(Url::fromPath(Dashboard::BASE_ROUTE . '/home', [
                'home' => $this->getName()
            ]));
        }
    }

    /**
     * Get this dashboard home's url
     *
     * Parent class would always report a default url if $this->url isn't
     * set, which we do it on purpose.
     *
     * @return \Icinga\Web\Url
     */
    public function getUrl()
    {
        return $this->url;
    }
}
