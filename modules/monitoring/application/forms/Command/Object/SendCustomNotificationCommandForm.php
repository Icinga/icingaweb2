<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Module\Monitoring\Command\Object\SendCustomNotificationCommand;
use Icinga\Web\Notification;

/**
 * Form to send custom notifications
 */
class SendCustomNotificationCommandForm extends ObjectsCommandForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->addDescription(
            $this->translate(
                'This command is used to send custom notifications for hosts or'
                . ' services.'
            )
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural(
            'Send custom notification',
            'Send custom notifications',
            count($this->objects)
        );
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            array(
                'textarea',
                'comment',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Comment'),
                    'description'   => $this->translate(
                        'If you work with other administrators, you may find it useful to share information about the'
                        . ' the host or service that is having problems. Make sure you enter a brief description of'
                        . ' what you are doing.'
                    )
                )
            ),
            array(
                'checkbox',
                'forced',
                array(
                    'label'         => $this->translate('Forced'),
                    'value'         => false,
                    'description'   => $this->translate(
                        'If you check this option, a notification is sent'
                        . 'regardless of the current time and whether'
                        . ' notifications are enabled.'
                    )
                )
            ),
            array(
                'checkbox',
                'broadcast',
                array(
                    'label'         => $this->translate('Broadcast'),
                    'value'         => false,
                    'description'   => $this->translate(
                        'If you check this option, a notification is sent to'
                        . ' all normal and escalated contacts.'
                    )
                )
            )
        ));
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $comment = new SendCustomNotificationCommand();
            $comment->setObject($object);
            $comment->setComment($this->getElement('comment')->getValue());
            $comment->setAuthor($this->request->getUser()->getUsername());
            $comment->setForced($this->getElement('forced')->isChecked());
            $comment->setBroadcast($this->getElement('broadcast')->isChecked());
            $this->getTransport($this->request)->send($comment);
        }
        Notification::success($this->translatePlural(
            'Send custom notification..',
            'Send custom notifications..',
            count($this->objects)
        ));
        return true;
    }
}
