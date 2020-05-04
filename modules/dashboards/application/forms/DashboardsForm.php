<?php

namespace Icinga\Module\Dashboards\Forms;

use Icinga\Authentication\Auth;
use Icinga\Module\Dashboards\Common\Database;
use ipl\Sql\Select;
use ipl\Web\Compat\CompatForm;

/**
 * Allows you to use the same form for editing and creating a dashboard or dashlet
 */
abstract class DashboardsForm extends CompatForm
{
    use Database;

    /**
     * Fetch all dashboards from the database and return them as array
     *
     * @return array
     */
    public function fetchDashboards()
    {
        $dashboards = [];

        $select = (new Select())
            ->columns('*')
            ->from('dashboard')
            ->where(['type = ?' => 'public']);

        $result = $this->getDb()->select($select);

        foreach ($result as $dashboard) {
            $dashboards[$dashboard->id] = $dashboard->name;
        }

        $query = (new Select())
            ->from('dashboard')
            ->columns('*')
            ->join('user_dashboard', 'user_dashboard.dashboard_id = dashboard.id')
            ->where([
                'type = ?' => 'private',
                'user_dashboard.user_name = ?' => Auth::getInstance()->getUser()->getUsername()
            ]);

        $result = $this->getDb()->select($query);

        foreach ($result as $userDashboard) {
            $dashboards[$userDashboard->id] = $userDashboard->name;
        }

        return $dashboards;
    }

    /**
     * Create a new public dashboard and return its id
     *
     * @param string $name
     *
     * @return int
     */
    public function createDashboard($name)
    {
        if ($this->getValue('user-dashboard') !== null) {
            $data = [
                'name' => $name,
                'type' => 'private'
            ];

            $db = $this->getDb();
            $db->insert('dashboard', $data);

            return $db->lastInsertId();
        } else {
            $data = [
                'name' => $name,
                'type' => 'public'
            ];

            $db = $this->getDb();
            $db->insert('dashboard', $data);

            return $db->lastInsertId();
        }
    }

    /**
     * Create a user specific dashlets and return its id
     *
     * @param int $id     The id of the selected dashboard
     *
     * @return integer
     */
    public function createUserDashlet($id)
    {
        if ($this->getValue('new-dashboard-name') !== null ||
            $this->checkForPrivateDashboard($id)) {
            $data = [
                'dashboard_id' => $this->fetchUserDashboardId($id),
                'name' => $this->getValue('name'),
                'url' => $this->getValue('url')
            ];
            $db = $this->getDb();
            $db->insert('dashlet', $data);

            return $db->lastInsertId();
        } else {
            $select = (new Select())
                ->from('user_dashboard')
                ->columns('dashboard_id')
                ->orderBy('dashboard_id DESC')
                ->limit(1);

            $dashboard = $this->getDb()->select($select)->fetch();

            $db = $this->getDb();
            $db->insert('dashlet', [
                'dashboard_id' => $dashboard->dashboard_id,
                'name' => $this->getValue('name'),
                'url' => $this->getValue('url')
            ]);

            return $db->lastInsertId();
        }
    }

    /**
     * If the dashboard is new created then fetch its id or the dashboard is already created and return just the param
     *
     * @param int $id       The id of new created or old dashboard
     *
     * @return int
     */
    public function fetchUserDashboardId($id)
    {
        if ($this->getValue('new-dashboard-name') !== null) {
            $select = (new Select())
                ->from('user_dashboard')
                ->columns('dashboard_id')
                ->orderBy('dashboard_id DESC')
                ->limit(1);

            $dashboard = $this->getDb()->select($select)->fetch();

            return $dashboard->dashboard_id;
        } else {
            return $id;
        }
    }

    /**
     * Check if the selected dashboard is private or not
     *
     * @param int $id   The id of the selected dashboard
     *
     * @return bool
     */
    public function checkForPrivateDashboard($id)
    {
        $select = (new Select())
            ->from('user_dashboard')
            ->columns('*')
            ->join('dashboard d', 'user_dashboard.dashboard_id = d.id')
            ->where([
                'dashboard_id = ?' => $id,
                'd.type = ?' => 'private'
            ]);

        $dashboard = $this->getDb()->select($select)->fetch();

        if ($dashboard) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Display the FormElement for creating a new dashboards and dashlets
     */
    public function displayForm()
    {
        $this->addElement('textarea', 'url', [
            'label' => 'Url',
            'placeholder' => 'Enter Dashlet Url',
            'required' => true,
            'rows' => '3'
        ]);

        $this->addElement('text', 'name', [
            'label' => 'Dashlet Name',
            'placeholder' => 'Enter Dashlet Name',
            'required' => true
        ]);

        $this->addElement('checkbox', 'new-dashboard', [
            'label' => 'New Dashboard',
            'class' => 'autosubmit',
        ]);

        $this->addElement('checkbox', 'user-dashboard', [
            'label' => 'Private Dashboard',
            'class' => 'autosubmit',
        ]);

        if ($this->getElement('new-dashboard')->getValue() === 'y') {
            $this->addElement('text', 'new-dashboard-name', [
                'label' => 'Dashboard Name',
                'placeholder' => 'New Dashboard Name',
                'required' => true,
            ]);
        } else {
            $this->addElement('select', 'dashboard', [
                'label' => 'Dashboard',
                'required' => true,
                'options' => $this->fetchDashboards()
            ]);
        }
    }
}
