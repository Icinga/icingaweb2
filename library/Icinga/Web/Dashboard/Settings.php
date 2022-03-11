<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use Icinga\Web\Dashboard\ItemList\DashboardHomeList;
use Icinga\Web\Dashboard\ItemList\DashboardList;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class Settings extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => ['dashboard-settings content']];

    protected $tag = 'div';

    /** @var Dashboard */
    protected $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function assemble()
    {
        // TODO: What we should with disabled homes??
        $activeHome = $this->dashboard->getActiveHome();

        if (empty($this->dashboard->getHomes())) {
            // TODO: No dashboard homes :( what should we render now??
        } elseif (count($this->dashboard->getHomes()) === 1 && $activeHome->getName() === DashboardHome::DEFAULT_HOME) {
            foreach ($activeHome->getPanes() as $pane) {
                $pane->setHome($activeHome);

                $this->addHtml(new DashboardList($pane));
            }

            $this->addHtml(new ActionLink(
                t('Add Dashboard'),
                Url::fromPath(Dashboard::BASE_ROUTE . '/new-dashlet'),
                'plus',
                [
                    'class'                 => 'add-dashboard',
                    'data-icinga-modal'     => true,
                    'data-no-icinga-ajax'   => true
                ]
            ));
        } else {
            // Make a list of dashbaord homes
            foreach ($this->dashboard->getHomes() as $home) {
                $this->addHtml(new DashboardHomeList($home));
            }
        }
    }
}
