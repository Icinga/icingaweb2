<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use \Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for deleting host or service downtimes
 */
class DeleteDowntimeCommandForm extends CommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setAttrib('class', 'inline');
    }
    
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(
            array(
                array(
                    'hidden',
                    'downtime_id',
                    array(
                        'required' => true,
                        'validators' => array('NotEmpty'),
                        'decorators' => array('ViewHelper')
                    )
                ),
                array(
                    'hidden',
                    'downtime_is_service',
                    array(
                        'filters' => array('Boolean'),
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
   
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For  the method documentation.
     */
    public function onSuccess()
    {
        $cmd = new DeleteDowntimeCommand();
        $cmd->setDowntimeId($this->getElement('downtime_id')->getValue());
        $cmd->setIsService($this->getElement('downtime_is_service')->getValue());
        $this->getTransport($this->request)->send($cmd);
    
        $redirect = $this->getElement('redirect')->getValue();
        if (! empty($redirect)) {
            $this->setRedirectUrl($redirect);
        }
        Notification::success($this->translate('Deleting downtime.'));
        return true;
    }
}
