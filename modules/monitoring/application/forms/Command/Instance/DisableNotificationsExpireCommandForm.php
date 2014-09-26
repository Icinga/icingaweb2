<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Instance\DisableNotificationsExpireCommand;
use Icinga\Module\Monitoring\Form\Command\CommandForm;
use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Web\Form\Element\Note;
use Icinga\Web\Notification;
use Icinga\Web\Request;

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
            new Note(
                'command-info',
                array(
                    'value' => mt(
                        'monitoring',
                        'This command is used to disable host and service notifications for a specific time.'
                    )
                )
            )
        );
        $expireTime = new DateTime();
        $expireTime->add(new DateInterval('PT1H'));
        $this->addElement(
            new DateTimePicker(
                'expire_time',
                array(
                    'required'      => true,
                    'label'         => mt('monitoring', 'Expire Time'),
                    'description'   => mt('monitoring', 'Set the expire time.'),
                    'value'         => $expireTime
                )
            )
        );
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
    {
        $disableNotifications = new DisableNotificationsExpireCommand();
        $disableNotifications
            ->setExpireTime($this->getElement('expire_time')->getValue()->getTimestamp());
        $this->getTransport($request)->send($disableNotifications);
        Notification::success(mt('monitoring', 'Disabling host and service notifications..'));
        return true;
    }
}
