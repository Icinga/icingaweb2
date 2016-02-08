<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
        $this->setRequiredCue(null);
        $this->setSubmitLabel($this->translate('Disable Notifications'));
        $this->addDescription($this->translate(
            'This command is used to disable host and service notifications for a specific time.'
        ));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $expireTime = new DateTime();
        $expireTime->add(new DateInterval('PT1H'));
        $this->addElement(
            'dateTimePicker',
            'expire_time',
            array(
                'required'      => true,
                'label'         => $this->translate('Expire Time'),
                'description'   => $this->translate('Set the expire time.'),
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
        Notification::success($this->translate('Disabling host and service notifications..'));
        return true;
    }
}
