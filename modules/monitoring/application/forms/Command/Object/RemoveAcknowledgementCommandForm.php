<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use Icinga\Module\Monitoring\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for removing host or service problem acknowledgements
 */
class RemoveAcknowledgementCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Remove Problem Acknowledgement'));
        $this->setAttrib('class', 'inline link-like');
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $removeAck = new RemoveAcknowledgementCommand();
            $removeAck->setObject($object);
            $this->getTransport($request)->send($removeAck);
        }
        Notification::success(mt('monitoring', 'Removing problem acknowledgement..'));
        return true;
    }
}
