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

namespace Monitoring\Form\Command;

use Icinga\Web\Form\Element\DateTime;
use Icinga\Protocol\Commandpipe\Downtime;
use Icinga\Protocol\Commandpipe\Comment;
use \DateTime as PhpDateTime;
use \DateInterval;
use \Zend_Form_Element_Text;
use \Zend_Validate_GreaterThan;
use \Zend_Validate_Digits;

/**
 * Form for any ScheduleDowntime command
 */
class ScheduleDowntimeForm extends WithChildrenCommandForm
{
    /**
     * Default endtime interval definition
     * @see http://php.net/manual/de/class.dateinterval.php
     */
    const DEFAULT_ENDTIME_INTERVAL = 'PT1H';

    /**
     * Type constant for fixed downtimes
     */
    const  TYPE_FIXED = 'fixed';

    /**
     * Type constant for flexible downtimes
     */
    const TYPE_FLEXIBLE = 'flexible';

    /**
     * Build an array of timestamps
     *
     * @return string[]
     */
    private function generateDefaultTimestamps()
    {
        $out = array();

        $dateTimeObject = new PhpDateTime();
        $out[] = $dateTimeObject->format($this->getDateFormat());

        $dateInterval = new DateInterval(self::DEFAULT_ENDTIME_INTERVAL);
        $dateTimeObject->add($dateInterval);
        $out[] = $dateTimeObject->format($this->getDateFormat());

        return $out;
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
     * Interface method to build the form
     *
     * @see ConfirmationForm::create
     */
    protected function create()
    {
        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'    => t('Comment'),
                'rows'     => 4,
                'required' => true
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

        list($timestampStart, $timestampEnd) = $this->generateDefaultTimestamps();

        $dateTimeStart = new DateTime(
            array(
                'name'  => 'starttime',
                'label' => t('Start time'),
                'value' => $timestampStart
            )
        );
        $dateTimeStart->setRequired(true);
        $dateTimeStart->addValidator($this->createDateTimeValidator(), true);

        $dateTimeEnd = new DateTime(
            array(
                'name'  => 'endtime',
                'label' => t('End time'),
                'value' => $timestampEnd
            )
        );
        $dateTimeEnd->setRequired(true);
        $dateTimeEnd->addValidator($this->createDateTimeValidator(), true);

        $this->addElements(array($dateTimeStart, $dateTimeEnd));

        $this->addElement(
            'select',
            'type',
            array(
                'label'        => t('Downtime type'),
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
                )
            )
        );

        $hoursText = new Zend_Form_Element_Text('hours');
        $hoursText->setLabel(t('Flexible duration'));
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

        if ($this->getWithChildren() === true) {
            $this->addNote(t('Schedule downtime for host and its services.'));
        } else {

            $this->addElement(
                'select',
                'childobjects',
                array(
                    'label'        => t('Child objects'),
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

            $this->addNote(t('Schedule downtime for this object.'));
        }

        $this->setSubmitLabel(t('Schedule downtime'));

        parent::create();
    }

    /**
     * Change validators at runtime
     *
     * @see Form::preValidation
     * @param array $data
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
     *  Return the downtime submitted in this form
     *
     *  @return Downtime
     */
    public function getDowntime()
    {

        $comment = new Comment(
            $this->getRequest()->getUser()->getUsername(),
            $this->getValue('comment')
        );
        $duration = 0;
        if ($this->getValue('type') === self::TYPE_FLEXIBLE) {
            $duration = ($this->getValue('hours')*3600) + ($this->getValue('minutes')*60);
        }
        $starttime = new PhpDateTime($this->getValue('starttime'));
        $endtime = new PhpDateTime($this->getValue('endtime'));

        $downtime = new Downtime(
            $starttime->getTimestamp(),
            $endtime->getTimestamp(),
            $comment,
            $duration,
            $this->getValue('triggered')
        );
        if (! $this->getWithChildren()) {
            switch ($this->getValue('childobjects')) {
                case 1:
                    $downtime->setType(Downtime::TYPE_WITH_CHILDREN_TRIGERRED);
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
