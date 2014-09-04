<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Service;

use Icinga\Module\Monitoring\Command\Service\ScheduleServiceDowntimeCommand;
use Icinga\Module\Monitoring\Form\Command\Common\ScheduleDowntimeCommandForm;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for scheduling a service downtime on an Icinga instance
 */
class ScheduleServiceDowntimeCommandForm extends ScheduleDowntimeCommandForm
{
    /**
     * Service to set in downtime
     *
     * @var Service
     */
    protected $service;

    /**
     * Set the service to set in downtime
     *
     * @param   Service $service
     *
     * @return  $this
     */
    public function setService(Service $service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Get the service to set in downtime
     *
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Schedule Service Downtime'));
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
                    'This command is used to schedule a service downtime. During the specified downtime,'
                    . ' Icinga will not send notifications out about the service. When the scheduled downtime'
                    . ' expires, Icinga will send out notifications for the service as it normally would.'
                    . ' Scheduled downtimes are preserved across program shutdowns and restarts.'
                )
            )
        );
        parent::createElements($formData);
        return $this;
    }

    /**
     * Get the command which is to be sent to an Icinga instance
     *
     * @return ScheduleServiceDowntimeCommand
     */
    public function getCommand()
    {
        return new ScheduleServiceDowntimeCommand();
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess(Request $request)
    {
        $scheduleDowntime = $this->getCommand();
        $scheduleDowntime->setService($this->service);
        $scheduleDowntime->setComment($this->getElement('comment')->getValue());
        $scheduleDowntime->setAuthor($request->getUser());
        $scheduleDowntime->setStart($this->getElement('start')->getValue());
        $scheduleDowntime->setEnd($this->getElement('end')->getValue());
        if ($this->getElement('type')->getValue() === self::FLEXIBLE) {
            $scheduleDowntime->setFlexible();
            $scheduleDowntime->setDuration(
                (float) $this->getElement('hours')->getValue() * 3600
                + (float) $this->getElement('minutes')->getValue() * 60
            );
        }
        $this->getTransport($request)->send($scheduleDowntime);
        Notification::success(mt('monitoring', 'Scheduling service downtime..'));
        return true;
    }
}
