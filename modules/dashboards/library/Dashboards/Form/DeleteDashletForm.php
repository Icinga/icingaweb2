<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Module\Dashboards\Common\Database;
use ipl\Html\Html;
use ipl\Web\Compat\CompatForm;

class DeleteDashletForm extends CompatForm
{
    use Database;

    /** @var object|null $dashlet Public dashlet from the given dashboard */
    protected $dashlet;

    /** @var object|null Private dashlet from the selected dashboard */
    protected $userDashlet;

    /**
     * Create a dashlet remove Form
     *
     * @param object|null $dashlet The dashlet that can be deleted by any user
     *
     * @param object|null $userDashlet The dashlet, which is only deleted if the user owns it
     */
    public function __construct($dashlet = null, $userDashlet = null)
    {
        $this->dashlet = $dashlet;
        $this->userDashlet = $userDashlet;
    }

    protected function assemble()
    {
        if (! empty($this->dashlet)) {
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

        if (! empty($this->userDashlet)) {
            $this->add(
                Html::tag(
                    'h1',
                    null,
                    Html::sprintf(
                        'Please confirm deletion of dashlet %s',
                        $this->userDashlet->name
                    )
                )
            );

            $this->addElement('input', 'btn_submit', [
                'type' => 'submit',
                'value' => 'Confirm Removal',
                'class' => 'btn-primary autofocus'
            ]);
        }
    }

    protected function onSuccess()
    {
        if (! empty($this->dashlet)) {
            $this->getDb()->delete('dashlet', ['id = ?' => $this->dashlet->id]);
        }

        if (! empty($this->userDashlet)) {
            $this->getDb()->delete('user_dashlet', ['id = ?' => $this->userDashlet->id]);
        }
    }
}
