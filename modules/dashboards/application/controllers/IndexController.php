<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\DashboardWidget;
use Icinga\Web\Url;
use ipl\Sql\Select;

class DashboardsController extends Controller
{
    use Database;

    public function indexAction()
    {
        $this->createTabs();

        $select = (new Select())
            ->columns('dashlet.name, dashlet.dashboard_id, dashlet.url')
            ->from('dashlet')
            ->join('dashboard d', 'dashlet.dashboard_id = d.id')
            ->where(['d.name = ?' => $this->getTabs()->getActiveName()]);

        $dashlets = $this->getDb()->select($select);

        $this->content = new DashboardWidget($dashlets);
    }

    protected function createTabs()
    {
        $activateDashboard = [];

        $tabs = $this->getTabs();
        $data = (new Select())
            ->columns('*')
            ->from('dashboard');

        $dashboards = $this->getDb()->select($data);

        foreach ($dashboards as $dashboard) {
            $tabs->add($dashboard->id, [
                'label' => $dashboard->name,
                'url' => Url::fromPath('dashboards/dashboards', [
                    'dashboard' => $dashboard->id
                ])
            ]);

            $ids[] = $dashboard->id;
        }

        foreach ($ids as $id) {
            $id = $this->params->get('dashboard') ? : array_shift($ids);
            $tabs->activate($id);

            return $id;
        }

        return $tabs;
    }
}
