<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Forms\Command\Instance;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Instance\DisableNotificationsExpireCommand;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Web\Notification;

/**
 * Form for disabling host and service notifications w/ an optional expire date and time on an Icinga instance
 */
class DisableNotificationsExpireCommandForm extends CommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Disable Notifications'));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElement(
            'note',
            'command-info',
            array(
                'value' => mt(
                    'monitoring',
                    'This command is used to disable host and service notifications for a specific time.'
                )
            )
        );
        $expireTime = new DateTime();
        $expireTime->add(new DateInterval('PT1H'));
        $this->addElement(
            'dateTimePicker',
            'expire_time',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Expire Time'),
                'description'   => mt('monitoring', 'Set the expire time.'),
                'value'         => $expireTime
            )
        );
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        $disableNotifications = new DisableNotificationsExpireCommand();
        $disableNotifications
            ->setExpireTime($this->getElement('expire_time')->getValue()->getTimestamp());
        $this->getTransport($this->request)->send($disableNotifications);
        Notification::success(mt('monitoring', 'Disabling host and service notifications..'));
        return true;
    }
}
