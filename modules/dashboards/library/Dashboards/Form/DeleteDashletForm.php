<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Module\Dashboards\Common\Database;
use ipl\Html\Html;
use ipl\Web\Compat\CompatForm;

class DeleteDashletForm extends CompatForm
{
    use Database;

    /** @var object $dashlet single dashlet from the given dashboard */
    protected $dashlet;

    /**
     * Create a dashlet remove Form
     *
     * @param object $dashlet The dashlet that is deleted from the given dashboard
     */
    public function __construct($dashlet)
    {
        $this->dashlet = $dashlet;
    }

    protected function assemble()
    {
        $this->add(
            Html::tag(
                'h1',
                null,
                Html::sprintf(
                    'Please confirm deletion of dashlet %s',
                    $this->dashlet->name
                )
            )
        );

        $this->addElement('input', 'btn_submit', [
            'type' => 'submit',
            'value' => 'Confirm Removal',
            'class' => 'btn-primary autofocus'
        ]);
    }

    protected function onSuccess()
    {
        $this->getDb()->delete('dashlet', ['id = ?' => $this->dashlet->id]);
    }
}
