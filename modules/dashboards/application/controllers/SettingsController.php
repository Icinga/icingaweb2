<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Web\Widget\DashboardSetting;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use ipl\Sql\Select;

class SettingsController extends Controller
{
    use Database;

    public function indexAction()
    {
        try {
            $this->createTabs();
        } catch (\Exception $err) {
            $this->tabs->extend(new DashboardAction())->disableLegacyExtensions();

            Notification::error("No dashboard found! Please create firstly a dashboard.");
        }

        $select = (new Select())
            ->from('dashboard')
            ->columns('*')
            ->where(['type = ?' => 'public']);

        $dashboard = $this->getDb()->select($select);

        $query = (new Select())
            ->from('dashboard')
            ->columns('*')
            ->join('user_dashboard d', 'd.dashboard_id = dashboard.id')
            ->where([
                'type = ?' => 'private',
                'd.user_name = ?' => Auth::getInstance()->getUser()->getUsername()
            ]);

        $userDashboard = $this->getDb()->select($query);

        $this->content = new DashboardSetting($dashboard, $userDashboard);
    }

    /**
     * Create a tab for each dashboard from the database
     *
     * @return \ipl\Web\Widget\Tabs
     *
     * @throws \Icinga\Exception\NotFoundError  If no dashboard is found to create a Tab
     */
    protected function createTabs()
    {
        $tabs = $this->getTabs();

        $select = (new Select())
            ->columns('*')
            ->from('dashboard')
            ->join('user_dashboard d', 'd.dashboard_id = dashboard.id')
            ->where([
                'dashboard.type = ?' => 'private',
                'd.user_name = ?' => Auth::getInstance()->getUser()->getUsername()
            ]);

        $userDashboards = $this->getDb()->select($select);

        foreach ($userDashboards as $userDashboard) {
            $tabs->add($userDashboard->name, [
                'label' => $userDashboard->name,
                'url' => Url::fromPath('dashboards', [
                    'dashboard' => $userDashboard->id
                ])
            ])->extend(new DashboardAction())->disableLegacyExtensions();
        }

        $select = (new Select())
            ->columns('*')
            ->from('dashboard')
            ->where(['type = ?' => 'public']);

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
