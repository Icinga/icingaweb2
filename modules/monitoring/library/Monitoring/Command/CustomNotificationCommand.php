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

/**
 * Command to send a custom notification
 */
class CustomNotificationCommand extends BaseCommand
{
    /**
     * The comment associated with this notification
     *
     * @var Comment
     */
    private $comment;

    /**
     * Whether this notification is forced
     *
     * @var bool
     */
    private $forced;

    /**
     * Whether this notification is also sent to escalation-contacts
     *
     * @var bool
     */
    private $broadcast;

    /**
     * Initialise a new custom notification command object
     *
     * @param   Comment     $comment    The comment for this notification
     * @param   bool        $forced     Whether this notificatin is forced
     * @param   bool        $broadcast  Whether this notification is sent to all contacts
     */
    public function __construct(Comment $comment, $forced = false, $broadcast = false)
    {
        $this->comment = $comment;
        $this->forced = $forced;
        $this->broadcast = $broadcast;
    }

    /**
     * Set the comment for this notification
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
     * Set whether this notification is forced
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setForced($state)
    {
        $this->forced = $state;
        return $this;
    }

    /**
     * Set whether this notification is sent to all contacts
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setBroadcast($state)
    {
        $this->broadcast = $state;
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
        $options = 0;
        if ($this->forced) {
            $options |= 2;
        }
        if ($this->broadcast) {
            $options |= 1;
        }
        return array_merge(array($options), $this->comment->getParameters(true));
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
        return 'SEND_CUSTOM_HOST_NOTIFICATION;' . implode(';', array_merge(array($hostname), $this->getParameters()));
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
        return 'SEND_CUSTOM_SVC_NOTIFICATION;' . implode(
            ';',
            array_merge(
                array($hostname, $servicename),
                $this->getParameters()
            )
        );
    }
}