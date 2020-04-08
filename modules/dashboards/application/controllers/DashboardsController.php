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

        $setAttribute = $this->content;
        $setAttribute->setAttributes(['class' => 'dashboard content']);

        $stmt = (new Select())
            ->columns('dashlet.name, dashlet.dashboard_id, dashlet.url')
            ->from('dashlet')
            ->join('dashboard d', 'dashlet.dashboard_id = d.id')
            ->where(['d.name = ?' => $this->getTabs()->getActiveName()]);

        $db = $this->getDb()->select($stmt);
        $dashlet = new DashboardWidget($db);

        $this->addContent($dashlet);
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
                'label' => $dashboard->name,
                'url' => Url::fromPath('dashboards/dashboards', [
                    'dashboard' => $dashboard->name
                ])
            ]);
        }

        $tabs->activate($this->getParam('dashboard'));

        return $tabs;
    }
}
