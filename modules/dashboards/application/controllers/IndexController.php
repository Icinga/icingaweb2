<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\DashboardWidget;
use Icinga\Web\Url;
use ipl\Sql\Select;

class IndexController extends Controller
{
    use Database;

    public function indexAction()
    {
        $this->createTabs();

        $select = (new Select())
            ->columns('dashlet.name, dashlet.dashboard_id, dashlet.url')
            ->from('dashlet')
            ->join('dashboard d', 'dashlet.dashboard_id = d.id')
            ->where(['d.id = ?' => $this->getTabs()->getActiveName()]);

        $dashlets = $this->getDb()->select($select);

        $this->content = new DashboardWidget($dashlets);
    }

    protected function createTabs()
    {
        $tabs = $this->getTabs();
        $data = (new Select())
            ->columns('*')
            ->from('dashboard');

        $dashboards = $this->getDb()->select($data);

        foreach ($dashboards as $dashboard) {
            $tabs->add($dashboard->id, [
                'label' => $dashboard->name,
                'url' => Url::fromPath('dashboards', [
                    'dashboard' => $dashboard->id
                ])
            ]);

            $ids[] = $dashboard->id;
        }

        if (empty($this->params->get('dashboard'))) {
            $id = $this->params->get('dashboard') ?: array_shift($ids);
            $tabs->activate($id);

            return $id;
        } else {
            $tabs->activate($this->params->get('dashboard'));
        }

        return $tabs;
    }
}
