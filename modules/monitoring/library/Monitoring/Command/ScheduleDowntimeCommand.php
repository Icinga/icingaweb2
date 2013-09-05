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

use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Exception\NotImplementedError;

/**
 * Command for scheduling a new downtime
 */
class ScheduleDowntimeCommand extends BaseCommand
{
    /**
     * When this downtime should start
     *
     * @var int     The time as UNIX timestamp
     */
    private $startTime;

    /**
     * When this downtime should end
     *
     * @var int     The time as UNIX timestamp
     */
    private $endTime;

    /**
     * The comment associated with this downtime
     *
     * @var Comment
     */
    private $comment;

    /**
     * Whether this is a fixed or flexible downtime
     *
     * @var bool
     */
    private $fixed;

    /**
     * The duration to use when this downtime is a flexible one
     *
     * @var int     In seconds
     */
    private $duration;

    /**
     * The ID of the downtime which triggers this one
     *
     * @var int
     */
    private $triggerId;

    /**
     * Set when to start this downtime
     *
     * @param   int     $startTime
     *
     * @return  self
     */
    public function setStart($startTime)
    {
        $this->startTime = intval($startTime);
        return $this;
    }

    /**
     * Set when to end this downtime
     *
     * @param   int     $endTime
     *
     * @return  self
     */
    public function setEnd($endTime)
    {
        $this->endTime = intval($endTime);
        return $this;
    }

    /**
     * Set the comment for this downtime
     *
     * @param   Comment     $comment
     *
     * @return  self
     */
    public function setComment(Comment $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Set whether this downtime is fixed or flexible
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setFixed($state)
    {
        $this->fixed = (bool) $state;
        return $this;
    }

    /**
     * Set the duration of this downtime
     *
     * @param   int     $duration
     *
     * @return  self
     */
    public function setDuration($duration)
    {
        $this->duration = intval($duration);
        return $this;
    }

    /**
     * Set the triggering id for this downtime
     *
     * @param   int     $triggerId
     *
     * @return  self
     */
    public function setTriggerId($triggerId)
    {
        $this->triggerId = intval($triggerId);
        return $this;
    }

    /**
     * Initialise a new command object to schedule a downtime
     *
     * @param   int         $startTime      When to start this downtime as UNIX timestamp
     * @param   int         $endTime        When to end this downtime as UNIX timestamp
     * @param   Comment     $comment        The comment to use for this downtime
     * @param   bool        $fixed          Whether this downtime is fixed or flexible
     * @param   int         $duration       How long in seconds this downtime should apply if flexible
     * @param   int         $triggerId      The ID of the triggering downtime
     */
    public function __construct($startTime, $endTime, Comment $comment, $fixed = true, $duration = 0, $triggerId = 0)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->comment = $comment;
        $this->fixed = $fixed;
        $this->duration = $duration;
        $this->triggerId = $triggerId;
    }

    /**
     * Return the command as a string for the given host or all of it's services
     *
     * @param   type    $hostname       The name of the host to insert
     * @param   type    $servicesOnly   Whether this downtime is for the given host or all of it's services
     *
     * @return  string                  The string representation of the command
     */
    public function getHostCommand($hostname, $servicesOnly = false)
    {
        throw new NotImplementedError();
    }

    /**
     * Return the command as a string for the given host and all of it's children hosts
     *
     * @param   string  $hostname       The name of the host to insert
     * @param   bool    $triggered      Whether it's children are triggered
     *
     * @return  string                  The string representation of the command
     */
    public function getPropagatedHostCommand($hostname, $triggered = false)
    {
        throw new NotImplementedError();
    }

    /**
     * Return the command as a string for the given service
     *
     * @param   type    $hostname       The name of the host to insert
     * @param   type    $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getServiceCommand($hostname, $servicename)
    {
        throw new NotImplementedError();
    }

    /**
     * Return the command as a string for all hosts or services of the given hostgroup
     *
     * @param   type    $hostgroup      The name of the hostgroup to insert
     * @param   type    $hostsOnly      Whether only hosts or services are taken into account
     *
     * @return  string                  The string representation of the command
     */
    public function getHostgroupCommand($hostgroup, $hostsOnly = true)
    {
        throw new NotImplementedError();
    }

    /**
     * Return the command as a string for all hosts or services of the given servicegroup
     *
     * @param   type    $servicegroup   The name of the servicegroup to insert
     * @param   type    $hostsOnly      Whether only hosts or services are taken into account
     *
     * @return  string                  The string representation of the command
     */
    public function getServicegroupCommand($servicegroup, $hostsOnly = true)
    {
        throw new NotImplementedError();
    }
}