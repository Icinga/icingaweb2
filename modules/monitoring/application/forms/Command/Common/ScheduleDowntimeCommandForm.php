<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Common;

use DateTime;
use DateInterval;
use Icinga\Module\Monitoring\Form\Command\CommandForm;
use Icinga\Web\Form\Element\DateTimePicker;
use Icinga\Web\Form\Element\Number;

/**
 * Base class for downtime command forms on an Icinga instance
 */
abstract class ScheduleDowntimeCommandForm extends CommandForm
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
                    'label'         => mt('monitoring', 'Comment'),
                    'description'   => mt(
                        'monitoring',
                        'If you work with other administrators, you may find it useful to share information about the'
                            . ' the host or service that is having problems. Make sure you enter a brief description of'
                            . ' what you are doing.'
                    )
                )
            ),
            new DateTimePicker(
                'start',
                array(
                    'required'      => true,
                    'label'         => t('Start Time'),
                    'description'   => mt('monitoring', 'Set the start date and time for the downtime.'),
                    'value'         => $start
                )
            ),
            new DateTimePicker(
                'end',
                array(
                    'required'      => true,
                    'label'         => t('End Time'),
                    'description'   => mt('monitoring', 'Set the end date and time for the downtime.'),
                    'value'         => $end
                )
            ),
            array(
                'select',
                'type',
                array(
                    'required'      => true,
                    'autosubmit'    => true,
                    'label'         => mt('monitoring', 'Type'),
                    'description'   => mt(
                        'monitoring',
                        'If you select the fixed option, the downtime will be in effect between the start and end'
                            . ' times you specify whereas a flexible downtime starts when the host or service enters a'
                            . ' problem state sometime between the start and end times you specified and lasts as long'
                            . ' as the duration time you enter. The duration fields do not apply for fixed downtimes.'
                    ),
                    'multiOptions' => array(
                        self::FIXED     => mt('monitoring', 'Fixed'),
                        self::FLEXIBLE  => mt('monitoring', 'Flexible')
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
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group'))
                )
            )
        );
        if (isset($formData['type']) && $formData['type'] === self::FLEXIBLE) {
            $this->addElements(array(
                new Number(
                    'hours',
                    array(
                        'required'  => true,
                        'label'     => mt('monitoring', 'Hours'),
                        'value'     => 2,
                        'min'       => -1
                    )
                ),
                new Number(
                    'minutes',
                    array(
                        'required'  => true,
                        'label'     => mt('monitoring', 'Minutes'),
                        'value'     => 0,
                        'min'       => -1
                    )
                )
            ));
            $this->addDisplayGroup(
                array('hours', 'minutes'),
                'duration',
                array(
                    'legend'        => mt('monitoring', 'Flexible Duration'),
                    'description'   => mt(
                        'monitoring',
                        'Enter here the duration of the downtime. The downtime will be automatically deleted after this'
                            . ' time expired.'
                    ),
                    'decorators' => array(
                        'FormElements',
                        array('HtmlTag', array('tag' => 'div', 'class' => 'control-group')),
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
}
