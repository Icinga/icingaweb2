<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Module\Dashboards\Common\Database;
use ipl\Html\Html;
use ipl\Web\Compat\CompatForm;

class DeleteDashboardForm extends CompatForm
{
    use Database;

    protected $dashboard;

    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function assemble()
    {
        $this->add(
            Html::tag('h1',
                null,
                Html::sprintf('Please confirm deletion of dashboard %s',
                    $this->dashboard->name)));

        $this->addElement('input', 'btn_submit', [
            'type' => 'submit',
            'value' => 'Confirm Removal',
            'class' => 'btn-primary autofocus'
        ]);
    }

    protected function onSuccess()
    {
        $this->getDb()->delete('dashlet', ['dashboard_id = ?' => $this->dashboard->id]);
        $this->getDb()->delete('dashboard', ['id = ?' => $this->dashboard->id]);
    }
}
