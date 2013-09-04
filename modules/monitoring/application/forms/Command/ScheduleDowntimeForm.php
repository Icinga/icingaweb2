<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use \Zend_Form_Element_Text;
use \Zend_Validate_GreaterThan;
use \Zend_Validate_Digits;
use \Icinga\Web\Form\Element\DateTimePicker;
use \Icinga\Protocol\Commandpipe\Downtime;
use \Icinga\Protocol\Commandpipe\Comment;
use \Icinga\Util\DateTimeFactory;

/**
 * Form for scheduling downtimes
 */
class ScheduleDowntimeForm extends WithChildrenCommandForm
{
    /**
     * Type constant for fixed downtimes
     */
    const  TYPE_FIXED = 'fixed';

    /**
     * Type constant for flexible downtimes
     */
    const TYPE_FLEXIBLE = 'flexible';

    /**
     * Initialize form
     */
    public function init()
    {
        $this->setName('ScheduleDowntimeForm');
    }

    /**
     * Generate translated multi options based on type constants
     *
     * @return array
     */
    private function getDowntimeTypeOptions()
    {
        return array(
            self::TYPE_FIXED    => t('Fixed'),
            self::TYPE_FLEXIBLE => t('Flexible')
        );
    }

    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(
            t(
                'This command is used to schedule downtime for hosts/services. During the specified downtime, '
                . 'Icinga will not send notifications out about the affected objects. When the scheduled '
                . 'downtime expires, Icinga will send out notifications as it normally would. Scheduled '
                . 'downtimes are preserved across program shutdowns and restarts.'
            )
        );

        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'    => t('Comment'),
                'rows'     => 4,
                'required' => true,
                'helptext' => t(
                    'If you work with other administrators, you may find it useful to share information '
                    . 'about a host or service that is having problems if more than one of you may be working on '
                    . 'it. Make sure you enter a brief description of what you are doing.'
                )
            )
        );

        /**
         * @TODO: Display downtime list (Bug #4496)
         *
         */
        $this->addElement(
            'text',
            'triggered',
            array(
                'label'      => t('Triggered by'),
                'value'      => 0,
                'required'   => true,
                'validators' => array(
                    array(
                        'Digits',
                        true
                    ),
                    array(
                        'GreaterThan',
                        true,
                        array(
                            'min' => -1
                        )
                    )
                )
            )
        );

        $now = DateTimeFactory::create();
        $this->addElement(
            new DateTimePicker(
                array(
                    'name'      => 'starttime',
                    'label'     => t('Start Time'),
                    'value'     => $now->getTimestamp(),
                    'patterns'  => $this->getValidDateTimeFormats(),
                    'helptext'  => t('Set the start date/time for the downtime.')
                )
            )
        );
        $this->addElement(
            new DateTimePicker(
                array(
                    'name'      => 'endtime',
                    'label'     => t('End Time'),
                    'value'     => $now->getTimestamp() + 3600,
                    'patterns'  => $this->getValidDateTimeFormats(),
                    'helptext'  => t('Set the end date/time for the downtime.')
                )
            )
        );

        $this->addElement(
            'select',
            'type',
            array(
                'label'        => t('Downtime Type'),
                'multiOptions' => $this->getDowntimeTypeOptions(),
                'required'     => true,
                'validators'   => array(
                    array(
                        'InArray',
                        true,
                        array(
                            array_keys($this->getDowntimeTypeOptions())
                        )
                    )
                ),
                'helptext'     => t(
                    'If you select the fixed option, the downtime will be in effect between the start and end '
                    . 'times you specify whereas a flexible downtime starts when the service enters a non-OK state '
                    . '(sometime between the start and end times you specified) and lasts as long as the duration '
                    . 'of time you enter. The duration fields do not apply for fixed downtime.'
                )
            )
        );
        $this->enableAutoSubmit(array('type'));


        if ($this->getRequest()->getPost('type') === self::TYPE_FLEXIBLE) {
            $hoursText = new Zend_Form_Element_Text('hours');
            $hoursText->setLabel(t('Flexible Duration'));
            $hoursText->setAttrib('style', 'width: 40px;');
            $hoursText->setValue(1);
            $hoursText->addDecorator('HtmlTag', array('tag' => 'dd', 'openOnly' => true));
            $hoursText->addDecorator(
                'Callback',
                array(
                    'callback' => function () {
                        return t('Hours');
                    }
                )
            );
            $minutesText = new Zend_Form_Element_Text('minutes');
            $minutesText->setLabel(t('Minutes'));
            $minutesText->setAttrib('style', 'width: 40px;');
            $minutesText->setValue(0);
            $minutesText->removeDecorator('HtmlTag');
            $minutesText->removeDecorator('Label');
            $minutesText->addDecorator(
                'Callback',
                array(
                    'callback' => function () {
                        return t('Minutes');
                    }
                )
            );
            $this->addElements(array($hoursText, $minutesText));
            $this->addNote(
                t(
                    'Enter here the duration of the downtime. Icinga will automatically delete the downtime '
                    . 'after this time expired.'
                )
            );
        }

        // TODO: As of the time of writing it's possible to set hosts AND services as affected by this command but
        // with children only makes sense on hosts
        if ($this->getWithChildren() === true) {
            $this->addNote(t('TODO: Help message when with children is enabled'));
        } else {
            $this->addElement(
                'select',
                'childobjects',
                array(
                    'label'        => t('Child Objects'),
                    'required'     => true,
                    'multiOptions' => array(
                        0 => t('Do nothing with child objects'),
                        1 => t('Schedule triggered downtime for all child objects'),
                        2 => t('Schedule non-triggered downtime for all child objects')
                    ),
                    'validators'   => array(
                        array(
                            'Digits',
                            true
                        ),
                        array(
                            'InArray',
                            true,
                            array(
                                array(0, 1, 2)
                            )
                        )
                    )
                )
            );
            $this->addNote(t('TODO: Help message when with children is disabled'));
        }

        $this->setSubmitLabel(t('Schedule Downtime'));

        parent::create();
    }

    /**
     * Change validators at runtime
     *
     * @param array $data   The user provided data that will go into validation
     *
     * @see Form::preValidation
     */
    protected function preValidation(array $data)
    {
        /*
         * Other values needed when downtime type change to flexible
         */
        if (isset($data['type']) && $data['type'] === self::TYPE_FLEXIBLE) {
            $greaterThanValidator = new Zend_Validate_GreaterThan(-1);
            $digitsValidator = new Zend_Validate_Digits();

            $hours = $this->getElement('hours');
            $hours->setRequired(true);
            $hours->addValidator($digitsValidator, true);
            $hours->addValidator($greaterThanValidator, true);

            $minutes = $this->getElement('minutes');
            $minutes->setRequired(true);
            $minutes->addValidator($digitsValidator, true);
            $minutes->addValidator($greaterThanValidator, true);
        }
    }

    /**
     * Create Downtime from request Data
     *
     * @return \Icinga\Protocol\Commandpipe\Downtime
     */
    public function getDowntime()
    {

        $comment = new Comment(
            $this->getRequest()->getUser()->getUsername(),
            $this->getValue('comment')
        );
        $duration = 0;
        if ($this->getValue('type') === self::TYPE_FLEXIBLE) {
            $duration = ($this->getValue('hours') * 3600) + ($this->getValue('minutes') * 60);
        }
        $starttime = $this->getValue('starttime');
        $endtime = $this->getValue('endtime');

        $downtime = new Downtime(
            $starttime,
            $endtime,
            $comment,
            $duration,
            $this->getValue('triggered')
        );
        if (!$this->getWithChildren()) {
            switch ($this->getValue('childobjects')) {
                case 1:
                    $downtime->setType(Downtime::TYPE_WITH_CHILDREN_TRIGGERED);
                    break;
                case 2:
                    $downtime->setType(Downtime::TYPE_WITH_CHILDREN);
                    break;
            }
        } else {
            $downtime->setType(Downtime::TYPE_HOST_SVC);
        }
        return $downtime;
    }
}
