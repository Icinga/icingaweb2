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

namespace Monitoring\Command;

use \Icinga\Protocol\Commandpipe\Command;
use \Icinga\Protocol\Commandpipe\CommandPipe;
use \Icinga\Protocol\Commandpipe\Acknowledgement;

class AcknowledgeCommand extends MonitoringCommand implements Command
{
    /**
     * When this acknowledgement should expire
     *
     * @var int
     */
    private $expireTime = -1;

    /**
     * Whether notifications are going to be sent out
     *
     * @var bool
     */
    private $notify;

    /**
     * Whether this acknowledgement is going to be stickied
     *
     * @var bool
     */
    private $sticky;

    /**
     * Set the time when this acknowledgement should expire
     *
     * @param int $expireTime
     * @return self
     */
    public function setExpireTime($expireTime)
    {
        $this->expireTime = $expireTime;
        return $this;
    }

    /**
     * Set whether notifications should be sent out
     *
     * @param bool $state
     * @return self
     */
    public function setNotify($state)
    {
        $this->notify = $state;
        return $this;
    }

    /**
     * Set whether this acknowledgement should be stickied
     *
     * @param bool $state
     * @return self
     */
    public function setSticky($state)
    {
        $this->sticky = $state;
        return $this;
    }

    /**
     * Create the acknowledgement object
     *
     * @return \Icinga\Protocol\Commandpipe\Acknowledgement
     */
    public function createAcknowledgement()
    {
        return new Acknowledgement(
            $this->comment,
            $this->notify,
            $this->expireTime,
            $this->sticky
        );
    }

    /**
     * @see Command::__toString()
     */
    public function __toString()
    {
        if (isset($this->service_description)) {
            $template = $this->createAcknowledgement()->getFormatString(CommandPipe::TYPE_SERVICE);
            return sprintf($template, $this->hostname, $this->service_description);
        } else {
            $template = $this->createAcknowledgement()->getFormatString(CommandPipe::TYPE_HOST);
            return sprintf($template, $this->hostname);
        }
    }
}