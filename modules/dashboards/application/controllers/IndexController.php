<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Authentication\Auth;
use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\Tabextension\DashboardAction;
use Icinga\Module\Dashboards\Web\Widget\DashboardWidget;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use ipl\Sql\Select;

class IndexController extends Controller
{
    use Database;

    public function indexAction()
    {
        try {
            $this->createTabsAndAutoActivateDashboard();
        } catch (\Exception $e) {
            $this->tabs->extend(new DashboardAction())->disableLegacyExtensions();

            Notification::error('No dashboard or dashlet found');
        }

        $selectUserDashlet = (new Select())
            ->columns([
                'user_dashlet.id',
                'user_dashlet.user_dashboard_id',
                'user_dashlet.name',
                'user_dashlet.url',
            ])
            ->from('user_dashlet')
            ->join('user_dashboard d', 'user_dashlet.user_dashboard_id = d.dashboard_id')
            ->join('dashboard', 'd.dashboard_id = dashboard.id')
            ->where([
                'dashboard_id = ?' => $this->tabs->getActiveName()
            ]);

        $select = (new Select())
            ->columns('dashlet.name, dashlet.dashboard_id, dashlet.url')
            ->from('dashlet')
            ->join('dashboard d', 'dashlet.dashboard_id = d.id')
            ->where([
                'd.id = ?' => $this->tabs->getActiveName()
            ]);

        $dashlets = $this->getDb()->select($select);

        $userDashlets = $this->getDb()->select($selectUserDashlet);

        $this->content = new DashboardWidget($dashlets, $userDashlets);
    }

    /**
     * create Tabs and
     * activate the first dashboard from the database when the url $params doesn't given
     *
     * @return int
     *
     * @throws \Icinga\Exception\NotFoundError      If the database doesn't have a dashboard
     */
    protected function createTabsAndAutoActivateDashboard()
    {
        $tabs = $this->getTabs();

        $select = (new Select())
            ->columns('*')
            ->from('dashboard')
            ->join('user_dashboard', 'user_dashboard.dashboard_id = dashboard.id')
            ->where([
                'type = ?' => 'private',
                'user_dashboard.user_name = ?' => Auth::getInstance()->getUser()->getUsername()
            ]);

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

        $select = (new Select())
            ->columns('*')
            ->from('dashboard')
            ->where(['type = ?' => 'public']);

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
