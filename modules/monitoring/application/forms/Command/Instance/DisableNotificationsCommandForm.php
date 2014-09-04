<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Instance;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Instance\ToggleNotifications;
use Icinga\Module\Monitoring\Form\Command\CommandForm;
use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for disabling host and service notifications w/ an optional expire date and time on an Icinga instance
 */
class DisableNotificationsCommandForm extends CommandForm
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
        $expire = new DateTime();
        $expire->add(new DateInterval('PT1H'));
        $this->addElement(
            new DateTimePicker(
                'expire',
                array(
                    'required'      => true,
                    'label'         => t('Expire Time'),
                    'description'   => mt('monitoring', 'Set the start date and time for the service downtime.'),
                    'value'         => $expire
                )
            )
        );
        return $this;
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ToggleNotifications
     */
    public function getCommand()
    {
        return new ToggleNotifications();
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
    {
        $toggleNotifications = $this->getCommand();
        $toggleNotifications
            ->disable()
            ->setExpire($this->getElement('expire')->getValue());
        $this->getTransport($request)->send($toggleNotifications);
        Notification::success(mt('monitoring', 'Command sent'));
        return true;
    }

}
