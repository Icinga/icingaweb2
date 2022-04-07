<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Web\Dashboard\ItemList\DashboardHomeList;
use Icinga\Web\Dashboard\ItemList\DashboardList;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

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
        $activeHome = $this->dashboard->getActiveHome();
        if (count($this->dashboard->getEntries()) === 1 && $activeHome->getName() === DashboardHome::DEFAULT_HOME) {
            $this->setAttribute('data-icinga-home', DashboardHome::DEFAULT_HOME);

            foreach ($activeHome->getEntries() as $pane) {
                $pane->setHome($activeHome);

                $this->addHtml(new DashboardList($pane));
            }

            $url = Url::fromPath(Dashboard::BASE_ROUTE . '/new-pane')
                ->setParams(['home' => $activeHome->getName()]);

            $this->addHtml(new ActionLink(t('Add Dashboard'), $url, 'plus', [
                'class'               => 'add-dashboard',
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]));
        } else {
            // Make a list of dashboard homes
            foreach ($this->dashboard->getEntries() as $home) {
                $this->addHtml(new DashboardHomeList($home));
            }
        }
    }
}
