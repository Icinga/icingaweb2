<?php

namespace Icinga\Module\Dashboards\Form;

use Exception;
use Icinga\Module\Dashboards\Model\Database;
use ipl\Html\Html;
use ipl\Web\Compat\CompatForm;

class DashletForm extends CompatForm
{
    use Database;
    private $data;

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

        $this->add(Html::tag('hr'));

        $this->addElement('select', 'dashboard', [
            'label' => 'Dashboard',
            'options' => [
                '1' => 'Current Incidents',
                '2' => 'Muted'
            ]
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
        try {
            $this->data = [
                'dashboard_id' => $this->getValue('dashboard'),
                'name'  => $this->getValue('name'),
                'url'   => $this->getValue('url'),
            ];

            $this->getDb()->insert('dashlet', $this->data);
        } catch (Exception $error) {
            throw $error;
        }
    }
}
