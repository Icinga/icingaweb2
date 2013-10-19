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

use Icinga\Module\Monitoring\Command\DisableNotificationWithExpireCommand;
use Icinga\Util\DateTimeFactory;
use Icinga\Web\Form\Element\DateTimePicker;

/**
 * Provide expiration when notifications should be disabled
 */
class DisableNotificationWithExpireForm extends CommandForm
{
    /**
     * Build form content
     */
    protected function create()
    {
        $this->addNote('Disable notifications for a specific time on a program-wide basis');

        $now = DateTimeFactory::create();
        $this->addElement(
            new DateTimePicker(
                array(
                    'name'      => 'expiretime',
                    'label'     => t('Expire Time'),
                    'value'     => $now->getTimestamp() + 3600,
                    'patterns'  => $this->getValidDateTimeFormats(),
                    'helptext'  => t(
                        'Enter the expire date/time for this acknowledgement here. Icinga will '
                        . ' delete the acknowledgement after this date expired.'
                    )
                )
            )
        );

        $this->setSubmitLabel('Disable notifications');

        parent::create();
    }


    /**
     * Create command object for CommandPipe protocol
     *
     * @return AcknowledgeCommand
     */
    public function createCommand()
    {
        $command = new DisableNotificationWithExpireCommand();
        $command->setExpirationTimestamp($this->getValue('expiretime'));
        return $command;
    }
}
