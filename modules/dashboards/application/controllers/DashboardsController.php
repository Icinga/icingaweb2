<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\DashletWidget;
use Icinga\Web\Url;
use ipl\Sql\Select;

class DashboardsController extends Controller
{
    use Database;

    public function init()
    {
        $this->createTabs();
        $divContent = $this->content;
        $divContent->setAttributes(['class' => 'dashboard content']);

        $selectDashlet = (new Select())
            ->columns('dashlet.name, dashlet.url')
            ->from('dashlet')
            ->join('dashboard d', 'dashlet.dashboard_id = d.id WHERE d.name="Current Incidents"');

        $data = $this->getDb()->select($selectDashlet);
        $dashlets = new DashletWidget($data);

        $this->addContent($dashlets);
    }

    public function indexAction()
    {

    }

    protected function createTabs()
    {
        $tabs = $this->getTabs();
        $data = (new Select())
            ->columns('*')
            ->from('dashboard');

        $dashboards = $this->getDb()->select($data);

        foreach ($dashboards as $dashboard) {
            $tabs->add($dashboard->name, [
                'label'     => $dashboard->name,
                'url'       => Url::fromPath('dashboards/dashboards', [
                    'dashboard' => $dashboard->id
                ])
            ]);

            if ($dashboard->id === 1) {
                $tabs->activate($dashboard->name);
            }
        }

        return $tabs;
    }
}
