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

use Icinga\Protocol\Commandpipe\Command;

/**
 * Command to schedule checks
 */
class ScheduleCheckCommand extends Command
{
    /**
     * When this check is scheduled
     *
     * @var int     The time as UNIX timestamp
     */
    private $checkTime;

    /**
     * Whether this check is forced
     *
     * @var bool
     */
    private $forced;

    /**
     * Initialises a new command object to schedule checks
     *
     * @param   int     $checkTime      The time as UNIX timestamp
     * @param   bool    $forced         Whether this check is forced
     */
    public function __construct($checkTime, $forced = false)
    {
        $this->checkTime = $checkTime;
        $this->forced = $forced;
    }

    /**
     * Set when to schedule this check
     *
     * @param   int     $checkTime      The time as UNIX timestamp
     *
     * @return self
     */
    public function setCheckTime($checkTime)
    {
        $this->checkTime = (int) $checkTime;
        return $this;
    }

    /**
     * Set whether this check is forced
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setForced($state)
    {
        $this->forced = (bool) $state;
        return $this;
    }

    /**
     * Return this command's parameters properly arranged in an array
     *
     * @return  array
     * @see     Command::getParameters()
     */
    public function getParameters()
    {
        return array($this->checkTime);
    }

    /**
     * Return the command as a string for the given host or all of it's services
     *
     * @param   string  $hostname       The name of the host to insert
     *
     * @return  string                  The string representation of the command
     * @see     Command::getHostCommand()
     */
    public function getHostCommand($hostname)
    {
        return sprintf(
            'SCHEDULE%s_HOST_%s;',
            $this->forced ? '_FORCED' : '',
            $this->onlyServices ? 'SVC_CHECKS' : 'CHECK'
        ) . implode(';', array_merge(array($hostname), $this->getParameters()));
    }

    /**
     * Return the command as a string for the given service
     *
     * @param   string  $hostname       The name of the host to insert
     * @param   string  $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     * @see     Command::getServiceCommand()
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return sprintf('SCHEDULE%s_SVC_CHECK;', $this->forced ? '_FORCED' : '')
            . implode(';', array_merge(array($hostname, $servicename), $this->getParameters()));
    }
}
