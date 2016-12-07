<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Application\Config;
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
            $this->translate('This command is used to send custom notifications about hosts or services.')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Send custom notification',  'Send custom notifications',  count($this->objects));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        $config = Config::module('monitoring');

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
                    'value'         => (bool) $config->get('settings', 'custom_notification_forced', false),
                    'description'   => $this->translate(
                        'If you check this option, the notification is sent out regardless of time restrictions and'
                        . ' whether or not notifications are enabled.'
                    )
                )
            )
        ));

        if (! $this->getBackend()->isIcinga2()) {
            $this->addElement(
                'checkbox',
                'broadcast',
                array(
                    'label'         => $this->translate('Broadcast'),
                    'value'         => (bool) $config->get('settings', 'custom_notification_broadcast', false),
                    'description'   => $this->translate(
                        'If you check this option, the notification is sent out to all normal and escalated contacts.'
                    )
                )
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\MonitoredObject $object */
            $notification = new SendCustomNotificationCommand();
            $notification
                ->setObject($object)
                ->setComment($this->getElement('comment')->getValue())
                ->setAuthor($this->request->getUser()->getUsername())
                ->setForced($this->getElement('forced')->isChecked());
            if (($broadcast = $this->getElement('broadcast')) !== null) {
                $notification->setBroadcast($broadcast->isChecked());
            }
            $this->getTransport($this->request)->send($notification);
        }
        Notification::success($this->translatePlural(
            'Sending custom notification..',
            'Sending custom notifications..',
            count($this->objects)
        ));
        return true;
    }
}
