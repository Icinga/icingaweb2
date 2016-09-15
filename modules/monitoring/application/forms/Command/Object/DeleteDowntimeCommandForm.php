<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service downtimes
 */
class DeleteDowntimeCommandForm extends CommandForm
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }

    /**
     * {@inheritdoc}
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'button',
            'btn_submit',
            array(
                'class'         => 'link-button spinner',
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                ),
                'escape'        => false,
                'ignore'        => true,
                'label'         => $this->getView()->icon('cancel'),
                'title'         => $this->translate('Delete this downtime'),
                'type'          => 'submit'
            )
        );
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(
            array(
                array(
                    'hidden',
                    'downtime_id',
                    array(
                        'decorators'    => array('ViewHelper'),
                        'required'      => true,
                        'validators'    => array('NotEmpty')
                    )
                ),
                array(
                    'hidden',
                    'downtime_is_service',
                    array(
                        'decorators'    => array('ViewHelper'),
                        'filters'       => array('Boolean')
                    )
                ),
                array(
                    'hidden',
                    'downtime_name',
                    array(
                        'decorators' => array('ViewHelper')
                    )
                ),
                array(
                    'hidden',
                    'redirect',
                    array(
                        'decorators' => array('ViewHelper')
                    )
                )
            )
        );
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $cmd = new DeleteDowntimeCommand();
        $cmd
            ->setDowntimeId($this->getElement('downtime_id')->getValue())
            ->setDowntimeName($this->getElement('downtime_name')->getValue())
            ->setIsService($this->getElement('downtime_is_service')->getValue());
        $this->getTransport($this->request)->send($cmd);

        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success($this->translate('Deleting downtime.'));
        return true;
    }
}
