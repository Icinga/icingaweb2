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

namespace Icinga\Backend\Statusdat\DataView;

use Icinga\Backend\DataView\ObjectRemappingView;
use \Icinga\Protocol\Statusdat\IReader;

/**
 * Class StatusdatHostView
 * @package Icinga\Backend\Statusdat\DataView
 */
class StatusdatHostView extends ObjectRemappingView
{
    /**
     * @var mixed
     */
    private $state;

    /**
     * @var array
     */
    protected $handlerParameters = array(
        "host" => "getHost",
        "downtimes_with_info" => "getDowntimes",
        "comments_with_info" => "getComments"
    );

    /**
     * @var array
     */
    protected $mappedParameters = array(
        "host_address" => "host_name",
        "host_name" => "host_name",
        "host_state" => "status.current_state",
        "host_output" => "status.plugin_output",
        "host_perfdata" => "status.long_plugin_output",
        "host_last_state_change" => "status.last_state_change",
        "host_check_command" => "check_command",
        "host_last_check" => "status.last_check",
        "host_next_check" => "status.next_check",
        "host_check_latency" => "status.check_latency",
        "host_check_execution_time" => "status.check_execution_time",
        "active_checks_enabled" => "status.active_checks_enabled",
        "acknowledged" => "status.problem_has_been_acknowledged",
        "host_acknowledged" => "status.problem_has_been_acknowledged",
        // "state" => "current_state"
    );

    /**
     * @param $item
     * @return null
     */
    public function getHost(&$item)
    {
        if (!isset($this->state["host"][$item->host_name])) {
            return null;
        }
        if (!isset($this->state["host"][$item->host_name])) {
            return null;
        }
        return $this->state["host"][$item->host_name];
    }

    /**
     * @param IReader $reader
     */
    public function __construct(IReader $reader)
    {
        $this->state = & $reader->getState();
    }
}
