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

use \Zend_Form_Element_Checkbox;
use \Icinga\Web\Form\Element\DateTimePicker;
use \Icinga\Util\DateTimeFactory;

/**
 * Form for scheduling checks
 */
class RescheduleNextCheckForm extends WithChildrenCommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(
            t(
                'This command is used to schedule the next check of hosts/services. Icinga will re-queue the '
                . 'check at the time you specify.'
            )
        );

        $this->addElement(
            new DateTimePicker(
                array(
                    'name'      => 'checktime',
                    'label'     => t('Check Time'),
                    'patterns'  => $this->getValidDateTimeFormats(),
                    'value'     => DateTimeFactory::create()->getTimestamp(),
                    'required'  => !$this->getRequest()->getPost('forcecheck'),
                    'helptext'  => t('Set the date/time when this check should be executed.')
                )
            )
        );

        $this->addElement(
            new Zend_Form_Element_Checkbox(
                array(
                    'name'     => 'forcecheck',
                    'label'    => t('Force Check'),
                    'value'    => true,
                    'helptext' => t(
                        'If you select this option, Icinga will force a check regardless of both what time the '
                        . 'scheduled check occurs and whether or not checks are enabled.'
                    )
                )
            )
        );

        // TODO: As of the time of writing it's possible to set hosts AND services as affected by this command but
        // with children only makes sense on hosts
        if ($this->getWithChildren() === true) {
            $this->addNote(t('TODO: Help message when with children is enabled'));
        } else {
            $this->addNote(t('TODO: Help message when with children is disabled'));
        }

        $this->setSubmitLabel(t('Reschedule Check'));

        parent::create();
    }

    /**
     * Return whether this is a forced check (force is checked)
     *
     * @return bool
     */
    public function isForced()
    {
        return $this->getValue('forcecheck') == true;
    }
}
