<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Dashboards\Web\Widget\DashboardWidget;
use Icinga\Web\Url;
use ipl\Sql\Select;

class IndexController extends Controller
{
    use Database;

    public function indexAction()
    {
        $select = (new Select())
            ->columns('dashlet.name, dashlet.dashboard_id, dashlet.url')
            ->from('dashlet')
            ->join('dashboard d', 'dashlet.dashboard_id = d.id')
            ->where(['d.id = ?' => $this->createTabsAndAutoActivateDashboard()]);

        $dashlets = $this->getDb()->select($select);

        $this->content = new DashboardWidget($dashlets);
    }

    /**
     * create Tabs and
     * activate the first dashboard from the database when the url doesn't have a parameter
     * @return int $id
     */
    protected function createTabsAndAutoActivateDashboard()
    {
        $tabs = $this->getTabs();
        $select = (new Select())
            ->columns('*')
            ->from('dashboard');

        $dashboards = $this->getDb()->select($select);

        foreach ($dashboards as $dashboard) {
            $tabs->add($dashboard->id, [
                'label' => $dashboard->name,
                'url' => Url::fromPath('dashboards', [
                    'dashboard' => $dashboard->id
                ])
            ])->extend(new DashboardAction())->disableLegacyExtensions();

            $ids[] = $dashboard->id;
        }

        $id = $this->params->get('dashboard') ?: array_shift($ids);
        $tabs->activate($id);

        return $id;
    }
}
