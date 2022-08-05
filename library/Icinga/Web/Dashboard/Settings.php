<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Web\Dashboard\ItemList\DashboardHomeList;
use ipl\Html\BaseHtmlElement;

class Settings extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'dashboard-settings'];

    protected $tag = 'div';

    /** @var Dashboard */
    protected $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function assemble()
    {
        $activeHome = $this->dashboard->getActiveEntry();
        if ($this->dashboard->countEntries() === 1 && $activeHome->isDefaultHome()) {
            $this->setAttribute('data-icinga-home', DashboardHome::DEFAULT_HOME);
            $this->addFrom((new DashboardHomeList($activeHome))->setHeaderDisabled());
        } else {
            // Make a list of dashboard homes
            foreach ($this->dashboard->getEntries() as $home) {
                if ($home->isDisabled()) {
                    continue;
                }

                $this->addHtml(new DashboardHomeList($home));
            }
        }
    }
}
