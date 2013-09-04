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

/**
 * Form for the delay notification command
 */
class DelayNotificationForm extends CommandForm
{
    /**
     * Maximum delay amount in minutes
     */
    const MAX_DELAY = 1440; // 1 day

    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->addNote(t('This command is used to delay the next problem notification that is sent out.'));

        $this->addElement(
            'text',
            'minutes',
            array(
                'label'         => t('Notification Delay (Minutes From Now)'),
                'style'         => 'width: 80px;',
                'value'         => 0,
                'required'      => true,
                'validators'    => array(
                    array(
                        'between',
                        true,
                        array(
                            'min' => 1,
                            'max' => self::MAX_DELAY
                        )
                    )
                ),
                'helptext'      => t(
                    'The notification delay will be disregarded if the host/service changes state before the next '
                    . 'notification is scheduled to be sent out.'
                )
            )
        );

        $this->setSubmitLabel(t('Delay Notification'));

        parent::create();
    }

    /**
     * Return the currently set delay time in seconds
     *
     * @return integer
     */
    public function getDelayTime()
    {
        return $this->getValue('minutes') * 60;
    }
}
