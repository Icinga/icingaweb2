<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\Backend;

/**
 * A Icinga host
 */
class Host extends MonitoredObject
{
    /**
     * Type of the Icinga host
     *
     * @var string
     */
    public $type = self::TYPE_HOST;

    /**
     * Prefix of the Icinga host
     *
     * @var string
     */
    public $prefix = 'host_';

    /**
     * Host name
     *
     * @var string
     */
    protected $host;

    /**
     * Create a new host
     *
     * @param Backend   $backend    Backend to fetch host information from
     * @param string    $host       Host name
     */
    public function __construct(Backend $backend, $host)
    {
        parent::__construct($backend);
        $this->host = $host;
    }

    /**
     * Get the host name
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the data view to fetch the host information from
     *
     * @return \Icinga\Module\Monitoring\DataView\HostStatus
     */
    protected function getDataView()
    {
        return $this->backend->select()->from('hostStatus', array(
            'host_name',
            'host_alias',
            'host_address',
            'host_state',
            'host_state_type',
            'host_handled',
            'host_in_downtime',
            'host_acknowledged',
            'host_last_state_change',
            'host_last_notification',
            'host_last_check',
            'host_next_check',
            'host_check_execution_time',
            'host_check_latency',
            'host_check_source',
            'host_output',
            'host_long_output',
            'host_check_command',
            'host_perfdata',
            'host_passive_checks_enabled',
            'host_passive_checks_enabled_changed',
            'host_obsessing',
            'host_obsessing_changed',
            'host_notifications_enabled',
            'host_notifications_enabled_changed',
            'host_event_handler_enabled',
            'host_event_handler_enabled_changed',
            'host_flap_detection_enabled',
            'host_flap_detection_enabled_changed',
            'host_active_checks_enabled',
            'host_active_checks_enabled_changed',
            'host_current_check_attempt',
            'host_max_check_attempts',
            'host_current_notification_number',
            'host_percent_state_change',
            'host_is_flapping',
            'host_action_url',
            'host_notes_url',
            'host_modified_host_attributes',
            'host_problem',
            'host_process_performance_data'
        ))
            ->where('host_name', $this->host);
    }
}
