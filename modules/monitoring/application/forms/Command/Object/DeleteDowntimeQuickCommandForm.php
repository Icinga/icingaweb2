<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use \Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service downtimes
 */
class DeleteDowntimeQuickCommandForm extends DeleteDowntimeCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::addSubmitButton() For the method documentation.
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'ignore'        => true,
                'escape'        => false,
                'type'          => 'submit',
                'class'         => 'link-like',
                'label'         => $this->getView()->icon('trash'),
                'title'         => $this->translate('Delete this downtime'),
                'decorators'    => array('ViewHelper')
            )
        );
        return $this;
    }
}
