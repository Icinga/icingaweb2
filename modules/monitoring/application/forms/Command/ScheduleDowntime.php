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
use \DateTime as PhpDateTime;
use \DateInterval;
use \Zend_Form_Element_Text;

/**
 * Form for any ScheduleDowntime command
 */
class ScheduleDowntime extends WithChildrenCommand
{
    /**
     * Default endtime interval definition
     * @see http://php.net/manual/de/class.dateinterval.php
     */
    const DEFAULT_ENDTIME_INTERVAL = 'PT1H';

    /**
     * Default time format
     * TODO(mh): Should be configurable on a central place (#4424)
     */
    const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

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
     * @return string[]
     */
    private function generateDefaultTimestamps()
    {
        $out = array();

        $dateTimeObject = new PhpDateTime();
        $out[] = $dateTimeObject->format(self::DEFAULT_DATE_FORMAT);

        $dateInterval = new DateInterval(self::DEFAULT_ENDTIME_INTERVAL);
        $dateTimeObject->add($dateInterval);
        $out[] = $dateTimeObject->format(self::DEFAULT_DATE_FORMAT);

        return $out;
    }

    /**
     * Generate translated multi options based on type constants
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
     * @see Form::create()
     */
    protected function create()
    {
        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label' => t('Comment'),
                'rows'  => 4
            )
        );

        $this->addElement(
            'text',
            'triggered',
            array(
                'label' => t('Triggered by')
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

        $dateTimeEnd = new DateTime(
            array(
                'name'  => 'endtime',
                'label' => t('End time'),
                'value' => $timestampEnd
            )
        );

        $this->addElements(array($dateTimeStart, $dateTimeEnd));

        $this->addElement(
            'select',
            'type',
            array(
                'label'        => t('Downtime type'),
                'multiOptions' => $this->getDowntimeTypeOptions()
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
                    'multiOptions' => array(
                        0 => t('Do nothing with child objects'),
                        1 => t('Schedule triggered downtime for all child objects'),
                        2 => t('Schedule non-triggered downtime for all child objects')
                    )
                )
            );

            $this->addNote(t('Schedule downtime for this object.'));
        }

        $this->setSubmitLabel(t('Schedule downtime'));

        parent::create();
    }
}
