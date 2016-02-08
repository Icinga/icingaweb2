<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceCheckCommand;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for scheduling service checks
 */
class ScheduleServiceCheckCommandForm extends ObjectsCommandForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->addDescription($this->translate(
            'This command is used to schedule the next check of hosts or services. Icinga will re-queue the'
            . ' hosts or services to be checked at the time you specify.'
        ));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Schedule check', 'Schedule checks', count($this->objects));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $checkTime = new DateTime();
        $checkTime->add(new DateInterval('PT1H'));
        $this->addElements(array(
            array(
                'dateTimePicker',
                'check_time',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Check Time'),
                    'description'   => $this->translate(
                        'Set the date and time when the check should be scheduled.'
                    ),
                    'value'         => $checkTime
                )
            ),
            array(
                'checkbox',
                'force_check',
                array(
                    'label'         => $this->translate('Force Check'),
                    'description'   => $this->translate(
                        'If you select this option, Icinga will force a check regardless of both what time the'
                        . ' scheduled check occurs and whether or not checks are enabled.'
                    )
                )
            )
        ));
        return $this;
    }

    /**
     * Schedule a check
     *
     * @param ScheduleServiceCheckCommand   $check
     * @param Request                       $request
     */
    public function scheduleCheck(ScheduleServiceCheckCommand $check, Request $request)
    {
        $check
            ->setForced($this->getElement('force_check')->isChecked())
            ->setCheckTime($this->getElement('check_time')->getValue()->getTimestamp());
        $this->getTransport($request)->send($check);
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\Service $object */
            $check = new ScheduleServiceCheckCommand();
            $check->setObject($object);
            $this->scheduleCheck($check, $this->request);
        }
        Notification::success($this->translatePlural(
            'Scheduling service check..',
            'Scheduling service checks..',
            count($this->objects)
        ));
        return true;
    }
}
