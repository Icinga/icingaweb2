<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Web\Notification;
use ipl\Sql\Select;
use ipl\Web\Compat\CompatForm;

class DashletForm extends CompatForm
{
    use Database;

    /**
     * Fetch all Dashboards from the database and return them as array
     * @return array $dashboards
     */
    public function fetchDashboards()
    {
        $dashboards = [];

        $select = (new Select())
            ->columns('*')
            ->from('dashboard');

        $result = $this->getDb()->select($select);

        foreach ($result as $dashboard) {
            $dashboards[$dashboard->id] = $dashboard->name;
        }

        return $dashboards;
    }

    /**
     * Create a new Dashboard and return the last insert Id
     * @param string $name
     * @return int $id
     */
    public function createDashboard($name)
    {
        $data = [
            'name' => $name
        ];

        $db = $this->getDb();
        $db->insert('dashboard', $data);

        $id = $db->lastInsertId();

        return $id;
    }

    /**
     * Display the FormElement for creating new Dashboards and Dashlets
     */
    public function newAction()
    {
        $this->setAction('dashboards/dashlets/new');

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
        if ($this->getValue('new-dashboard')) {
            if ($this->getValue('new-dashboard-name') !== null) {
                $values = [
                    'dashboard_id' => $this->createDashboard($this->getValue('new-dashboard-name')),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url')
                ];

                $this->getDb()->insert('dashlet', $values);
                Notification::success('Dashlet in new Dashboard created');
            } else {
                Notification::error('Dashboard Name failed!');
            }

        } else {
            $data = [
                'dashboard_id' => $this->getValue('dashboard'),
                'name' => $this->getValue('name'),
                'url' => $this->getValue('url'),
            ];

            $this->getDb()->insert('dashlet', $data);
            Notification::success('Dashlet created');
        }
    }
}
