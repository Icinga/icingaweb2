<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceDowntimeCommand;
use Icinga\Web\Notification;
use Icinga\Web\Request;

/**
 * Form for scheduling service downtimes
 */
class ScheduleServiceDowntimeCommandForm extends ObjectsCommandForm
{
    /**
     * Fixed downtime
     */
    const FIXED = 'fixed';

    /**
     * Flexible downtime
     */
    const FLEXIBLE = 'flexible';

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->addDescription($this->translate(
            'This command is used to schedule host and service downtimes. During the specified downtime,'
            . ' Icinga will not send notifications out about the hosts and services. When the scheduled'
            . ' downtime expires, Icinga will send out notifications for the hosts and services as it'
            . ' normally would. Scheduled downtimes are preserved across program shutdowns and'
            . ' restarts.'
        ));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::getSubmitLabel() For the method documentation.
     */
    public function getSubmitLabel()
    {
        return $this->translatePlural('Schedule downtime', 'Schedule downtimes', count($this->objects));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $start = new DateTime;
        $end = clone $start;
        $end->add(new DateInterval('PT1H'));
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
                'dateTimePicker',
                'start',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Start Time'),
                    'description'   => $this->translate('Set the start date and time for the downtime.'),
                    'value'         => $start
                )
            ),
            array(
                'dateTimePicker',
                'end',
                array(
                    'required'      => true,
                    'label'         => $this->translate('End Time'),
                    'description'   => $this->translate('Set the end date and time for the downtime.'),
                    'value'         => $end
                )
            ),
            array(
                'select',
                'type',
                array(
                    'required'      => true,
                    'autosubmit'    => true,
                    'label'         => $this->translate('Type'),
                    'description'   => $this->translate(
                        'If you select the fixed option, the downtime will be in effect between the start and end'
                        . ' times you specify whereas a flexible downtime starts when the host or service enters a'
                        . ' problem state sometime between the start and end times you specified and lasts as long'
                        . ' as the duration time you enter. The duration fields do not apply for fixed downtimes.'
                    ),
                    'multiOptions' => array(
                        self::FIXED     => $this->translate('Fixed'),
                        self::FLEXIBLE  => $this->translate('Flexible')
                    ),
                    'validators' => array(
                        array(
                            'InArray',
                            true,
                            array(array(self::FIXED, self::FLEXIBLE))
                        )
                    )
                )
            )
        ));
        $this->addDisplayGroup(
            array('start', 'end'),
            'start-end',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div'))
                )
            )
        );
        if (isset($formData['type']) && $formData['type'] === self::FLEXIBLE) {
            $this->addElements(array(
                array(
                    'number',
                    'hours',
                    array(
                        'required'  => true,
                        'label'     => $this->translate('Hours'),
                        'value'     => 2,
                        'min'       => -1
                    )
                ),
                array(
                    'number',
                    'minutes',
                    array(
                        'required'  => true,
                        'label'     => $this->translate('Minutes'),
                        'value'     => 0,
                        'min'       => -1
                    )
                )
            ));
            $this->addDisplayGroup(
                array('hours', 'minutes'),
                'duration',
                array(
                    'legend'        => $this->translate('Flexible Duration'),
                    'description'   => $this->translate(
                        'Enter here the duration of the downtime. The downtime will be automatically deleted after this'
                        . ' time expired.'
                    ),
                    'decorators' => array(
                        'FormElements',
                        array('HtmlTag', array('tag' => 'div')),
                        array(
                            'Description',
                            array('tag' => 'span', 'class' => 'description', 'placement' => 'prepend')
                        ),
                        'Fieldset'
                    )
                )
            );
        }
        return $this;
    }

    public function scheduleDowntime(ScheduleServiceDowntimeCommand $downtime, Request $request)
    {
        $downtime
            ->setComment($this->getElement('comment')->getValue())
            ->setAuthor($request->getUser()->getUsername())
            ->setStart($this->getElement('start')->getValue()->getTimestamp())
            ->setEnd($this->getElement('end')->getValue()->getTimestamp());
        if ($this->getElement('type')->getValue() === self::FLEXIBLE) {
            $downtime->setFixed(false);
            $downtime->setDuration(
                (float) $this->getElement('hours')->getValue() * 3600
                + (float) $this->getElement('minutes')->getValue() * 60
            );
        }
        $this->getTransport($request)->send($downtime);
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::onSuccess() For the method documentation.
     */
    public function onSuccess()
    {
        $end = $this->getValue('end')->getTimestamp();
        if ($end <= $this->getValue('start')->getTimestamp()) {
            $endElement = $this->_elements['end'];
            $endElement->setValue($endElement->getValue()->format($endElement->getFormat()));
            $endElement->addError($this->translate('The end time must be greater than the start time'));
            return false;
        }

        $now = new DateTime;
        if ($end <= $now->getTimestamp()) {
            $endElement = $this->_elements['end'];
            $endElement->setValue($endElement->getValue()->format($endElement->getFormat()));
            $endElement->addError($this->translate('A downtime must not be in the past'));
            return false;
        }

        foreach ($this->objects as $object) {
            /** @var \Icinga\Module\Monitoring\Object\Service $object */
            $downtime = new ScheduleServiceDowntimeCommand();
            $downtime->setObject($object);
            $this->scheduleDowntime($downtime, $this->request);
        }
        Notification::success($this->translatePlural(
            'Scheduling service downtime..',
            'Scheduling service downtimes..',
            count($this->objects)
        ));
        return true;
    }
}
