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

namespace Icinga\Module\Monitoring\Command;

/**
 * Command to delay a notification
 */
class DelayNotificationCommand extends BaseCommand
{
    /**
     * The delay in seconds
     *
     * @var int
     */
    private $delay;

    /**
     * Initialise a new delay notification command object
     *
     * @param   int     $delay      How long, in seconds, notifications should be delayed
     */
    public function __construct($delay)
    {
        $this->delay = $delay;
    }

    /**
     * Set how long notifications should be delayed
     *
     * @param   int     $seconds    In seconds
     *
     * @return  self
     */
    public function setDelay($seconds)
    {
        $this->delay = intval($seconds);
        return $this;
    }

    /**
     * Return this command's parameters properly arranged in an array
     *
     * @return array
     *
     * @see BaseCommand::getParameters()
     */
    public function getParameters()
    {
        return array($this->delay);
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string  $hostname   The name of the host to insert
     *
     * @return  string              The string representation of the command
     *
     * @see BaseCommand::getHostCommand()
     */
    public function getHostCommand($hostname)
    {
        return 'DELAY_HOST_NOTIFICATION;' . implode(';', array_merge(array($hostname), $this->getParameters()));
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string  $hostname       The name of the host to insert
     * @param   string  $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     *
     * @see BaseCommand::getServiceCommand()
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return 'DELAY_SVC_NOTIFICATION;' . implode(
            ';',
            array_merge(
                array($hostname, $servicename),
                $this->getParameters()
            )
        );
    }
}