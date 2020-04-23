<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Web\Widget\DashboardSetting;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Url;
use ipl\Sql\Select;

class SettingsController extends Controller
{
    use Database;

    public function indexAction()
    {
        $this->createTabs();

        $select = (new Select())
            ->from('dashboard')
            ->columns('*');

        $dashboard = $this->getDb()->select($select);

        $dashboardSetting = new DashboardSetting($dashboard);

        $this->addContent($dashboardSetting);
    }

    /**
     * Create a tab for each dashboard from the database
     *
     * @return \ipl\Web\Widget\Tabs
     */
    protected function createTabs()
    {
        $tabs = $this->getTabs();

        $select = (new Select())
            ->columns('*')
            ->from('dashboard');

        $dashboards = $this->getDb()->select($select);

        foreach ($dashboards as $dashboard) {
            $tabs->add($dashboard->name, [
                'label' => $dashboard->name,
                'url' => Url::fromPath('dashboards', [
                    'dashboard' => $dashboard->id
                ])
            ])->extend(new DashboardAction())->disableLegacyExtensions();
        }

        return $tabs;
    }
}
