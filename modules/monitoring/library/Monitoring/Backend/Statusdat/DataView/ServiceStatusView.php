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
use Icinga\Protocol\Statusdat\IReader;

/**
 * Class StatusdatHostView
 * @package Icinga\Backend\Statusdat\DataView
 */
class ServiceStatusView extends ObjectRemappingView
{
    /**
     * @var mixed
     */
    private $state;

    /**
     * @var array
     */
    protected $handlerParameters = array(
        "host"                          => "getHost",
        "host_last_comment"             => "getLastHostComment",
        'host_handled'                  => "checkIfHostHandled",
        'service_handled'               => "checkIfHandled",
        "service_last_comment"          => "getLastComment"
    );

    public function checkIfHostHandled(&$service)
    {
        return $service->host->status->current_state == 0 ||
            $service->host->status->problem_has_been_acknowledged ||
            $service->host->status->scheduled_downtime_depth;
    }


    public function checkIfHandled(&$service)
    {
        return $service->status->current_state == 0 ||
            $service->status->problem_has_been_acknowledged ||
            $service->status->scheduled_downtime_depth;
    }

    public function getLastComment(&$service)
    {
        if (!isset($service->comment) || empty($service->comment)) {
            return null;
        }
        $comment = end($service->comment);
        return $comment->comment_id;
    }

    public function getLastHostComment(&$service)
    {
        if (!isset($service->host->comment) || empty($service->host->comment)) {
            return null;
        }
        $comment = end($service->host->comment);
        return $comment->comment_id;
    }

    /**
     * @var array
     */
    public static $mappedParameters = array(
        "host_address"                  => "host.address",
        "host_name"                     => "host.host_name",
        "host"                          => "host.host_name",
        "host_state"                    => "host.status.current_state",
        "host_output"                   => "host.status.plugin_output",
        "host_long_output"              => "host.status.long_plugin_output",
        "host_perfdata"                 => "host.status.performance_data",
        "host_last_state_change"        => "host.status.last_state_change",
        "host_check_command"            => "host.check_command",
        "host_last_check"               => "TO_DATE(host.status.last_check)",
        "host_next_check"               => "host.status.next_check",
        "host_check_latency"            => "host.status.check_latency",
        "host_check_execution_time"     => "host.status.check_execution_time",
        "host_active_checks_enabled"    => "host.status.active_checks_enabled",
        "host_in_downtime"              => "host.status.scheduled_downtime_depth",
        "host_is_flapping"              => "host.status.is_flapping",
        "host_notifications_enabled"    => "host.status.notifications_enabled",
        "host_state_type"               => "host.status.state_type",
        "host_icon_image"               => "host.icon_image",
        "host_action_url"               => "host.action_url",
        "host_notes_url"                => "host.notes_url",
        "host_acknowledged"             => "host.status.problem_has_been_acknowledged",
        "service"                       => "service_description",
        "service_display_name"          => "service_description",
        "service_description"           => "service_description",
        "service_state"                 => "status.current_state",
        "service_icon_image"            => "icon_image",
        "service_output"                => "status.plugin_output",
        "service_long_output"           => "status.long_plugin_output",
        "service_perfdata"              => "status.performance_data",
        "service_last_state_change"     => "status.last_state_change",
        "service_check_command"         => "check_command",
        "service_last_check"            => "TO_DATE(status.last_check)",
        "service_next_check"            => "status.next_check",
        "service_check_latency"         => "status.check_latency",
        "service_check_execution_time"  => "status.check_execution_time",
        "service_active_checks_enabled" => "status.active_checks_enabled",
        "service_in_downtime"           => "status.scheduled_downtime_depth",
        "service_is_flapping"           => "status.is_flapping",
        "service_notifications_enabled" => "status.notifications_enabled",
        "service_state_type"            => "status.state_type",
        "service_icon_image"            => "icon_image",
        "service_action_url"            => "action_url",
        "service_notes_url"             => "notes_url",
        "service_acknowledged"          => "status.problem_has_been_acknowledged",
        // "state" => "current_state"
    );

    /**
     * @param $item
     * @return null
     */
    public function getHost(&$item)
    {
        if (!isset($this->state["service"][$item->host_name])) {
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
        $this->state = $reader->getState();
    }
}
