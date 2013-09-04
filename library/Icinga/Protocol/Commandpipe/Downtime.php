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

namespace Icinga\Protocol\Commandpipe;

/**
 * Container class containing downtime information
 *
 */
class Downtime
{
    /**
     * Propagate this downtime for all child objects
     */
    const TYPE_WITH_CHILDREN = 'AND_PROPAGATE_';

    /**
     * Propagate this downtime for all child objects as triggered downtime
     */
    const TYPE_WITH_CHILDREN_TRIGGERED = 'AND_PROPAGATE_TRIGGERED_';

    /**
     * Schedule downtime for the services of the given hos
     */
    const TYPE_HOST_SVC = 'HOST_SVC';

    /**
     * Timestamp representing the downtime's start
     *
     * @var int
     */
    public $startTime;

    /**
     * Timestamp representing the downtime's end
     *
     * @var int
     */
    public $endTime;

    /**
     * Whether this is a fixed downtime
     *
     * @var boolean
     */
    private $fixed = false;

    /**
     * The duration of the downtime in seconds if flexible
     *
     * @var int
     */
    public $duration;

    /**
     * The comment object of the downtime
     *
     * @var Comment
     */
    public $comment;

    /**
     * The downtime id that triggers this downtime (0 = no triggered downtime)
     *
     * @var int
     */
    public $trigger_id = 0;

    /**
     * Internal information for the exact type of the downtime
     *
     * E.g. with children, with children and triggered, services etc.
     *
     * @var string
     */
    private $subtype = '';

    /**
     * Create a new downtime container
     *
     * @param int $start            A timestamp that defines the downtime's start time
     * @param int $end              A timestamp that defines the downtime's end time
     * @param Comment $comment      A comment that will be used when scheduling the downtime
     * @param int $duration         The duration of this downtime in seconds.
     *                              Duration > 0 will make this a flexible downtime
     * @param int $trigger_id       An id of the downtime that triggers this downtime.
     *                              0 means this is not a triggered downtime
     */
    public function __construct($start, $end, Comment $comment, $duration = 0, $trigger_id = 0)
    {
        $this->startTime = $start;
        $this->endTime = $end;
        $this->comment = $comment;
        if ($duration == 0) {
            $this->fixed = true;
        }
        $this->duration = intval($duration);
        $this->trigger_id = intval($trigger_id);
    }

    /**
     * Return the SCHEDULE_?_DOWNTIME representing this class for the given $type
     *
     * @param string $type      CommandPipe::TYPE_SERVICE to trigger a service downtime or CommandPipe::TYPE_HOST to
     *                          trigger a host downtime
     * @return string           A schedule downtime command representing the state of this class
     *
     */
    public function getFormatString($type)
    {
        if ($this->subtype == self::TYPE_HOST_SVC) {
            $type = "";
        }
        return 'SCHEDULE_'
            . $this->subtype
            . $type
            . '_DOWNTIME;'
            . '%s;'
            . ($type == CommandPipe::TYPE_SERVICE ? '%s;' : '')
            . $this->startTime . ';'
            . $this->endTime . ';'
            . ($this->fixed ? '1' : '0') . ';'
            . $this->trigger_id  . ';'
            . $this->duration . ';'
            . $this->comment->author . ';'
            . $this->comment->content;
    }

    /**
     * Set the exact type of this downtime (see the TYPE_ constants)
     *
     * @param $type     The type of to use for this downtime
     */
    public function setType($type)
    {
        $this->subtype = $type;
    }
}
