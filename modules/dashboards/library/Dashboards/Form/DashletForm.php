<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Module\Dashboards\Model\Database;
use ipl\Sql\Select;
use ipl\Web\Compat\CompatForm;

class DashletForm extends CompatForm
{
    use Database;

    public function fetchDashboards()
    {
        $dashboards = [];

        $select = (new Select())
            ->columns('*')
            ->from('dashboard');

        $data = $this->getDb()->select($select);

        foreach ($data as $dashboard) {
            $dashboards[$dashboard->id] = $dashboard->name;
        }

        return $dashboards;
    }

    public function fetchNewDashboardId()
    {
        $dashboardIds = [];

        $data = ['name' => $this->getValue('new_dashboard')];

        $this->getDb()->insert('dashboard', $data);

        $newDashboard = (new Select())
            ->columns('id')
            ->from('dashboard')
            ->orderBy('id DESC')
            ->limit('1');

        $selectID = $this->getDb()->select($newDashboard);
        foreach ($selectID as $id) {
            $dashboardIds[$id->id] = $id->id;
        }

        return $dashboardIds;
    }


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

        $this->addElement('text', 'new_dashboard', [
            'label'     => 'New Dashboard',
            'placeholder'   => 'New Dashboard Name '
        ]);

        $this->addElement('select', 'dashboard', [
            'label' => 'Dashboard',
            'required'  => true,
            'options' => $this->fetchDashboards()
        ]);

        $this->addElement('submit', 'submit', [
            'label' => 'Add To Dashboard'
        ]);
    }

    protected function assemble()
    {
        $this->add($this->newAction());
    }

    public function onSuccess()
    {
        if ($this->getValue('new_dashboard') != null) {
            $values = [
                'dashboard_id'  => implode($this->fetchNewDashboardId()),
                'name'          => $this->getValue('name'),
                'url'           => $this->getValue('url')
            ];

            $this->getDb()->insert('dashlet', $values);
        } else {
            $data = [
                'dashboard_id' => $this->getValue('dashboard'),
                'name'  => $this->getValue('name'),
                'url'   => $this->getValue('url'),
            ];

            $this->getDb()->insert('dashlet', $data);
        }
    }
}
