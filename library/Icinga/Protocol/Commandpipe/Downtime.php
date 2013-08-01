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
 * Class Downtime
 * @package Icinga\Protocol\Commandpipe
 */
class Downtime
{
    const TYPE_WITH_CHILDREN = 'AND_PROPAGATE_';
    const TYPE_WITH_CHILDREN_TRIGERRED = 'AND_PROPAGATE_TRIGGERED_';
    const TYPE_HOST_SVC = 'HOST_SVC';
    /**
     * @var mixed
     */
    public $startTime;

    /**
     * @var mixed
     */
    public $endTime;

    /**
     * @var mixed
     */
    private $fixed = false;

    /**
     * @var mixed
     */
    public $duration;

    /**
     * @var mixed
     */
    public $comment;

    /**
     * @var int
     */
    public $trigger_id = 0;

    private $subtype = '';

    /**
     * @param $start
     * @param $end
     * @param Comment $comment
     * @param int $duration
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
     * @param $type
     * @return string
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
            . $this->comment->comment;
    }

    public function setType($type)
    {
        $this->subtype = $type;
    }
}
