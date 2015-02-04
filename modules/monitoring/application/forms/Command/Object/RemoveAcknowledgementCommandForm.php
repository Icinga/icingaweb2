<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Web\Notification;

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
        $this->setAttrib('class', 'inline link-like');
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return mtp(
            'monitoring', 'Remove problem acknowledgement', 'Remove problem acknowledgements', count($this->objects)
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $removeAck = new RemoveAcknowledgementCommand();
            $removeAck->setObject($object);
            $this->getTransport($this->request)->send($removeAck);
        }
        Notification::success(mtp(
            'monitoring',
            'Removing problem acknowledgement..',
            'Removing problem acknowledgements..',
            count($this->objects)
        ));
        return true;
    }
}
