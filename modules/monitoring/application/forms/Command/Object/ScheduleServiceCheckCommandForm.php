<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceCheckCommand;
use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for scheduling service checks
 */
class ScheduleServiceCheckCommandForm extends ObjectsCommandForm
{
    /**
     * (non-PHPDoc)
     * @see \Zend_Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setSubmitLabel(mt('monitoring', 'Schedule Check'));
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
                'note',
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
                            . 'scheduled check occurs and whether or not checks are enabled.'
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
    public function onSuccess(Request $request)
    {
        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\Service $object */
            $check = new ScheduleServiceCheckCommand();
            $check
                ->setObject($object)
                ->setForced((bool) $this->getElement('force_check')->getValue())
                ->setCheckTime($this->getElement('check_time')->getValue());
            $this->getTransport($request)->send($check);
        }
        Notification::success(mt('monitoring', 'Scheduling service check..'));
        return true;
    }
}
