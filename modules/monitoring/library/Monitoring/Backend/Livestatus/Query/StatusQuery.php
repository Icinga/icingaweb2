<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Livestatus\Query;

use Icinga\Protocol\Livestatus\Query;

class StatusQuery extends Query
{
    /**
     * This mode represents whether we are in HostStatus or ServiceStatus
     *
     * Implemented for `distinct as workaround
     *
     * @TODO Subject to change, see #7344
     *
     * @var string
     */
    protected $mode;

    /**
     * Sets the mode of the current query
     *
     * @TODO Subject to change, see #7344
     *
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    protected $table = 'services';

    protected $filter_flags = array(
        'host_handled'      => 'host_state > 0 & (host_acknowledged | host_in_downtime)',
        'host_problem'      => 'host_state > 0',
        'service_problem'   => 'service_state > 0',
        'service_handled'   => 'service_state > 0 & (host_state > 0 | service_acknowledged | service_in_downtime)',
        'service_unhandled' => 'service_state > 0 & host_state = 0 & !service_acknowledged & !service_in_downtime',
    );

    protected $available_columns = array(
        'host'              => 'host_name',
        'host_name'         => 'host_name',
        'host_display_name' => 'host_display_name',
        'host_alias'        => 'host_alias',
        'host_address'      => 'host_address',
        'host_ipv4'         => 'host_address', // TODO
        'host_icon_image'   => 'host_icon_image',
'host_contacts' => 'host_contacts',
        'host_problem'      => array('host_state'),
        'host_handled'      => array('host_state', 'host_acknowledged', 'host_scheduled_downtime_depth'),
        'service_problem'   => array('state', 'acknowledged', 'scheduled_downtime_depth'),
        'service_handled'   => array('host_state', 'state', 'acknowledged', 'scheduled_downtime_depth'),
        'service_unhandled' => array('host_state', 'state', 'acknowledged', 'scheduled_downtime_depth'),


// 'host_unhandled_services' => 'services_with_state', // Needs handler
// 'host_unhandled_services' => 'host_services_with_state', -> bringt nix, ist [service, state, has_been_checked]
'host_unhandled_services' => 'state', // Needs handler

'host_severity'    => array('host_state', 'host_acknowledged', 'host_scheduled_downtime_depth'),
'service_severity' => array('host_state', 'state', 'acknowledged', 'scheduled_downtime_depth'),





// TODO: Make these 1 if > 1
'host_in_downtime' => 'host_scheduled_downtime_depth',
'service_in_downtime' => 'scheduled_downtime_depth',


'host_check_latency' => 'host_latency',
'host_check_execution_time' => 'host_execution_time',

'host_long_output' => 'host_long_plugin_output',


'host_passive_checks_enabled_changed' => 'state',
'host_obsessing'                      => 'state',
'host_obsessing_changed'              => 'state',
'host_notifications_enabled_changed'  => 'state',
'host_event_handler_enabled_changed'  => 'state',
'host_flap_detection_enabled_changed' => 'state',
'host_active_checks_enabled_changed'  => 'state',

// TODO: Do we need two of them?
'host_current_check_attempt'             => 'host_current_attempt',
'host_attempt'                           => 'host_current_attempt',

'host_modified_host_attributes'          => 'host_modified_attributes',

'service_modified_service_attributes'    => 'modified_attributes',

'service_notifications_enabled_changed'  => 'modified_attributes_list',
'service_active_checks_enabled_changed'  => 'modified_attributes_list',
'service_passive_checks_enabled_changed' => 'modified_attributes_list',
'service_flap_detection_enabled_changed' => 'modified_attributes_list',
'service_event_handler_enabled_changed'  => 'modified_attributes_list',

'service_check_execution_time' => 'execution_time',
'service_check_latency' => 'latency',
'service_obsessing' => 'state',
'service_obsessing_changed' => 'state',

'service_hard_state' => 'state',

'service_attempt' => 'current_attempt',
'service_current_check_attempt' => 'current_attempt',

'host' => 'host_name',
'service_host_name' => 'host_name',
'service' => 'description',
'service_is_flapping' => 'is_flapping',
'service_long_output'                    => 'long_plugin_output',

'service_icon_image' => 'icon_image',
'service_action_url' => 'action_url',
'service_notes_url' => 'notes_url',
'host_max_check_attempts' => 'host_max_check_attempts',
'service_max_check_attempts' => 'max_check_attempts',

        // Host comments
        'host_last_comment' => 'comments_with_info',
        'host_last_ack' => 'comments_with_info',
        'host_last_downtime' => 'comments_with_info',
'host_check_command' => 'host_check_command',
        // Host state
        'host_state' => 'host_state',
        'host_state_type' => 'host_state_type',
        'host_output'                    => 'host_plugin_output',
        'host_perfdata'                  => 'host_perf_data',
        'host_acknowledged' => 'host_acknowledged',
        'host_active_checks_enabled'        => 'host_active_checks_enabled',
        'host_passive_checks_enabled'     => 'host_accept_passive_checks',
        'host_last_state_change' => 'host_last_state_change',

'host_event_handler_enabled' => 'host_event_handler_enabled',
'host_flap_detection_enabled' => 'host_flap_detection_enabled',
'host_current_notification_number' => 'host_current_notification_number',
'host_percent_state_change' => 'host_percent_state_change',
'host_process_performance_data' => 'host_process_performance_data',
'host_event_handler_enabled' => 'host_event_handler_enabled',
'host_flap_detection_enabled' => 'host_flap_detection_enabled',

'service_percent_state_change' => 'percent_state_change',

'host_last_notification' => 'host_last_notification',
'host_next_check' => 'host_next_check',
'host_check_source' => 'state',

        // Service config
        'service_description'            => 'description',
        'service_display_name'           => 'display_name',

        // Service state
        'service_state'                  => 'state',
        'service_output'                 => 'plugin_output',


        'service_state_type' => 'state_type',

        'service_perfdata'               => 'perf_data',
        'service_acknowledged'           => 'acknowledged',
        'service_active_checks_enabled'  => 'active_checks_enabled',
        'service_passive_checks_enabled' => 'accept_passive_checks',
        'service_last_check'      => 'last_check',
        'service_last_state_change'      => 'last_state_change',
        'service_notifications_enabled' => 'notifications_enabled',
        'service_last_notification' => 'last_notification',
'service_next_check' => 'next_check',
'service_last_time_unknown' => 'last_time_unknown',
'service_event_handler_enabled' => 'event_handler_enabled',

        // Service comments
        'service_last_comment' => 'comments_with_info',
        'service_last_ack' => 'comments_with_info',
        'service_last_downtime' => 'comments_with_info',
        'downtimes_with_info' => 'downtimes_with_info',
'service_check_command' => 'check_command',
'service_check_source' => 'state',
'service_current_notification_number' => 'current_notification_number',
'host_is_flapping' => 'host_is_flapping',
'host_last_check' => 'host_last_check',
'host_notifications_enabled' => 'host_notifications_enabled',
'host_action_url' => 'host_action_url',
'host_notes_url' => 'host_notes_url',
'host_last_hard_state' => 'host_last_hard_state',
'host_last_hard_state_change' => 'host_last_hard_state_change',
'host_last_time_up' => 'host_last_time_up',
'host_last_time_down' => 'host_last_time_down',
'host_last_time_unreachable' => 'host_last_time_unreachable',
'service_last_hard_state' => 'last_hard_state',
'service_last_hard_state_change' => 'last_hard_state_change',
'service_last_time_ok' => 'last_time_ok',
'service_last_time_warning' => 'last_time_warning',
'service_last_time_critical' => 'last_time_critical',
'service_flap_detection_enabled' => 'flap_detection_enabled',
'service_process_performance_data' => 'process_performance_data',
    );

    public function mungeResult_custom_variables($val, & $row)
    {
        $notseen = $this->customvars;
        foreach ($val as $cv) {
            $name = '_service_' . $cv[0];
            $row->$name = $cv[1];
            unset($notseen[$name]);
        }
        foreach ($notseen as $k => $v) {
            $row->$k = $v;
        }
    }

    public function mungeResult_service_last_comment($val, & $row)
    {
        $this->mungeResult_comments_with_info($val, $row);
    }

    public function mungeResult_service_last_ack($val, & $row)
    {
        $this->mungeResult_comments_with_info($val, $row);
    }

    public function mungeResult_service_last_downtime($val, & $row)
    {
        $this->mungeResult_comments_with_info($val, $row);
    }

    public function mungeResult_comments_with_info($val, & $row)
    {
        if (empty($val)) {
            $row->service_last_comment = $row->service_last_ack
                = $row->service_last_downtime = null;
        } else {
            $row->service_last_comment = $row->service_last_ack
                = $row->service_last_downtime = preg_replace('/\n/', ' ', print_r($val, 1));
        }
    }

    public function mungeResult_host_unhandled_services($val, & $row)
    {
        $cnt = 0;
        foreach ($this->parseArray($val) as $service) {
            if (! isset($service[1])) {
                continue;
                // TODO: More research is required here, on Icinga2 I got
                //       array(1) { [0]=> array(1) { [0]=> string(1) "2" } }
                var_dump($this->parseArray($val));
            }
            if ($service[1] > 0) {
                $cnt++;
            }
        }
        $row->host_unhandled_services = $cnt;
    }
}
