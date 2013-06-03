<?php
namespace Icinga\Backend\Statusdat\DataView;
use \Icinga\Protocol\Statusdat\IReader;
class StatusdatHostView extends \Icinga\Backend\DataView\ObjectRemappingView
{
    private $state;

    protected $handlerParameters = array(
        "host" => "getHost",
        "downtimes_with_info" => "getDowntimes",
        "comments_with_info" => "getComments"
    );

    protected $mappedParameters = array(
        "host_address" => "host_name",
        "host_name" => "host_name",
        "host_state" => "status.current_state",
        "host_output" => "status.plugin_output",
        "host_perfdata" => "status.long_plugin_output",
        "host_state" => "status.current_state",
        "host_perfdata" => "status.long_plugin_output",
        "host_last_state_change" => "status.last_state_change",
        "host_output" => "status.plugin_output",
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


    public function getHost(&$item) {
        if(!isset($this->state["host"][$item->host_name]))
            return null;
        if(!isset($this->state["host"][$item->host_name]))
            return null;
        return $this->state["host"][$item->host_name];
    }

    public function __construct(IReader $reader) {
        $this->state = &$reader->getState();
    }
}
