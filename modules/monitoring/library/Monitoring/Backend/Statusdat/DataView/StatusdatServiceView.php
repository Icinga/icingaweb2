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

namespace Icinga\Module\Monitoring\Backend\Statusdat\DataView;

use Icinga\Protocol\Statusdat\View\ObjectRemappingView;
use \Icinga\Protocol\Statusdat\IReader;

class StatusdatServiceView extends ObjectRemappingView
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
        "downtimes_with_info" => "getDowntimes"
    );

    /**
     * @var array
     */
    public static $mappedParameters = array(
        "host_address" => "parenthost.address",
        "host_name" => "host_name",
        "active_checks_enabled" => "status.active_checks_enabled",
        "passive_checks_enabled" => "status.passive_checks_enabled",
        "service_state" => "status.current_state",
        "service_perfdata" => "status.performance_data",
        "service_last_state_change" => "status.last_state_change",
        "service_output" => "status.plugin_output",
        "service_long_output" => "status.long_plugin_output",
        "service_check_command" => "check_command",
        "service_last_check" => "status.last_check",
        "service_next_check" => "status.next_check",
        "service_check_latency" => "status.check_latency",
        "service_check_execution_time" => "status.check_execution_time",
        "service_acknowledged" => "status.problem_has_been_acknowledged",
        "service_comments" => "comment"

    );

    /**
     * @param \Icinga\Backend\DataView\The $item
     * @param \Icinga\Backend\DataView\The $field
     * @return \Icinga\Backend\DataView\The|string
     */
    public function get(&$item, $field)
    {
        if (!isset($item->parenthost) && isset($this->state["host"])) {
            $item->parenthost = $this->state["host"];
        }

        return parent::get($item, $field);
    }

    /**
     * @param \Icinga\Backend\DataView\The $item
     * @param \Icinga\Backend\DataView\The $field
     * @return bool
     */
    public function exists(&$item, $field)
    {
        if (!isset($item->parenthost)) {
            $item->parenthost = $this->state["host"];
        }

        return parent::exists($item, $field);
    }

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
