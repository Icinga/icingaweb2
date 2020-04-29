<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Authentication\Auth;
use Icinga\Module\Dashboards\Common\Database;
use Icinga\Web\Notification;
use ipl\Sql\Select;
use ipl\Web\Compat\CompatForm;

class DashletForm extends CompatForm
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
     * Create a new dashboard and return its id
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

    public function createUserDashboard($name)
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
            return $name;
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
            ->where(['dashboard_id = ?' => $id]);

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
    public function newAction()
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

        $this->addElement('submit', 'submit', [
            'label' => 'Add To Dashboard'
        ]);
    }

    protected function assemble()
    {
        $this->add($this->newAction());
    }

    protected function onSuccess()
    {
        if ($this->getValue('user-dashboard') !== null) {
            if ($this->getValue('new-dashboard-name') !== null) {
                $data = [
                    'dashboard_id' => $this->createDashboard($this->getValue('new-dashboard-name')),
                    'user_name' => Auth::getInstance()->getUser()->getUsername()
                ];

                $this->getDb()->insert('user_dashboard', $data);

                $this->getDb()->insert('user_dashlet', [
                    'user_dashboard_id' => $this->createUserDashboard($this->getValue('new-dashboard-name')),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url')
                ]);

                Notification::success("Private dashboard and dashlet created");
            } else {
                if (! $this->checkForPrivateDashboard($this->getValue('dashboard'))) {
                    $this->getDb()->insert('user_dashboard', [
                        'dashboard_id' => $this->getValue('dashboard'),
                        'user_name' => Auth::getInstance()->getUser()->getUsername()
                    ]);

                    $select = (new Select())
                        ->from('user_dashboard')
                        ->columns('dashboard_id')
                        ->orderBy('dashboard_id DESC')
                        ->limit(1);

                    $dashboard = $this->getDb()->select($select)->fetch();

                    $this->getDb()->insert('user_dashlet', [
                        'user_dashboard_id' => $dashboard->dashboard_id,
                        'name' => $this->getValue('name'),
                        'url' => $this->getValue('url')
                    ]);

                    Notification::success("Private dashlet in a public dashboard created!");
                } else {
                    $this->getDb()->insert('user_dashlet', [
                        'user_dashboard_id' => $this->createUserDashboard($this->getValue('dashboard')),
                        'name' => $this->getValue('name'),
                        'url' => $this->getValue('url')
                    ]);

                    Notification::success("Private dashlet created");
                }
            }
        } elseif($this->checkForPrivateDashboard($this->getValue('dashboard'))) {
            Notification::error("You can't create public dashlet in a private dashboard!");
        } else {

            if ($this->getValue('new-dashboard-name') !== null) {
                $this->getDb()->insert('dashlet', [
                    'dashboard_id' => $this->createDashboard($this->getValue('new-dashboard-name')),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url')
                ]);

                Notification::success('Dashboard and dashlet created');
            } else {
                $this->getDb()->insert('dashlet', [
                    'dashboard_id' => $this->getValue('dashboard'),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url'),
                ]);

                Notification::success('Dashlet created');
            }
        }
    }
}
