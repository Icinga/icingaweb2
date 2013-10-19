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

use Icinga\Exception\ProgrammingError;
use Icinga\Protocol\Commandpipe\Command;

/**
 * Disable notifications with expire
 */
class DisableNotificationWithExpireCommand extends Command
{
    /**
     * Timestamp when deactivation should expire
     *
     * @var integer
     */
    private $expirationTimestamp;

    /**
     * Create a new instance of this command
     */
    public function __construct()
    {
        // There is now object specific implementation, only global
        $this->globalCommand = true;
    }

    /**
     * Setter for expiration timestamp
     *
     * @param integer $timestamp
     */
    public function setExpirationTimestamp($timestamp)
    {
        $this->expirationTimestamp = $timestamp;
    }

    /**
     * Return this command's arguments in the order expected by the actual command definition
     *
     * @return array
     */
    public function getArguments()
    {
        return array($this->expirationTimestamp);
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string $hostname   The name of the host to insert
     * @throws  ProgrammingError
     *
     * @return  string              The string representation of the command
     */
    public function getHostCommand($hostname)
    {
        throw new ProgrammingError('This is not supported for single objects');
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string $hostname       The name of the host to insert
     * @param   string $servicename    The name of the service to insert
     * @throws  ProgrammingError
     * @return  string                 The string representation of the command#
     */
    public function getServiceCommand($hostname, $servicename)
    {
        throw new ProgrammingError('This is not supported for single objects');
    }

    /**
     * Create a global command
     *
     * @param   string $instance
     *
     * @return  string
     */
    public function getGlobalCommand($instance = null)
    {
        return sprintf(
            'DISABLE_NOTIFICATIONS_EXPIRE_TIME;%d;%s',
            time(),
            implode(';', $this->getArguments())
        );
    }
}
