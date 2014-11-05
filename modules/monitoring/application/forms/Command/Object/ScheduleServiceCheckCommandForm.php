<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceCheckCommand;
use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Web\Form\Element\Note;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for scheduling service checks
 */
class ScheduleServiceCheckCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return mtp(
            'monitoring', 'Schedule check', 'Schedule checks', count($this->objects)
        );
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
            new Note(
                'command-info',
                array(
                    'value' => mt(
                        'monitoring',
                        'This command is used to schedule the next check of hosts or services. Icinga will re-queue the'
                        . ' hosts or services to be checked at the time you specify.'
                    )
                )
            ),
            new DateTimePicker(
                'check_time',
                array(
                    'required'      => true,
                    'label'         => mt('monitoring', 'Check Time'),
                    'description'   => mt('monitoring', 'Set the date and time when the check should be scheduled.'),
                    'value'         => $checkTime
                )
            ),
            array(
                'checkbox',
                'force_check',
                array(
                    'label'         => mt('monitoring', 'Force Check'),
                    'description'   => mt(
                        'monitoring',
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
    public function onSuccess(Request $request)
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\Service $object */
            $check = new ScheduleServiceCheckCommand();
            $check->setObject($object);
            $this->scheduleCheck($check, $request);
        }
        Notification::success(mtp(
            'monitoring',
            'Scheduling service check..',
            'Scheduling service checks..',
            count($this->objects)
        ));
        return true;
    }
}
