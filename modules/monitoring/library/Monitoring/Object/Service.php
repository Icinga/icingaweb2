<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Object;

use InvalidArgumentException;
use Icinga\Module\Monitoring\Backend;

/**
 * A Icinga service
 */
class Service extends MonitoredObject
{
    /**
     * Service state 'OK'
     */
    const STATE_OK = 0;

    /**
     * Service state 'WARNING'
     */
    const STATE_WARNING = 1;

    /**
     * Service state 'CRITICAL'
     */
    const STATE_CRITICAL = 2;

    /**
     * Service state 'UNKNOWN'
     */
    const STATE_UNKNOWN = 3;

    /**
     * Service state 'PENDING'
     */
    const STATE_PENDING = 99;

    /**
     * Type of the Icinga service
     *
     * @var string
     */
    public $type = self::TYPE_SERVICE;

    /**
     * Prefix of the Icinga service
     *
     * @var string
     */
    public $prefix = 'service_';

    /**
     * Host the service is running on
     *
     * @var Host
     */
    protected $host;

    /**
     * Service name
     *
     * @var string
     */
    protected $service;

    /**
     * Create a new service
     *
     * @param Backend   $backend    Backend to fetch service information from
     * @param string    $host       Host name the service is running on
     * @param string    $service    Service name
     */
    public function __construct(Backend $backend, $host, $service)
    {
        parent::__construct($backend);
        $this->host = new Host($backend, $host);
        $this->service = $service;
    }

    /**
     * Get the host the service is running on
     *
     * @return Host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the service name
     *
     * @return string
     */
    public function getName()
    {
        return $this->service;
    }

    /**
     * Get the data view
     *
     * @return \Icinga\Module\Monitoring\DataView\ServiceStatus
     */
    protected function getDataView()
    {
        return $this->backend->select()->from('serviceStatus', array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_problem',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_unhandled',
            'service_output',
            'service_last_state_change',
            'service_icon_image',
            'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_notifications_enabled_changed',
            'service_action_url',
            'service_notes_url',
            'service_last_check',
            'service_next_check',
            'service_attempt',
            'service_last_notification',
            'service_check_command',
            'service_check_source',
            'service_current_notification_number',
            'host_icon_image',
            'host_acknowledged',
            'host_output',
            'host_long_output',
            'host_in_downtime',
            'host_is_flapping',
            'host_last_check',
            'host_notifications_enabled',
            'host_unhandled_services',
            'host_action_url',
            'host_notes_url',
            'host_display_name',
            'host_alias',
            'host_ipv4',
            'host_severity',
            'host_perfdata',
            'host_active_checks_enabled',
            'host_passive_checks_enabled',
            'host_last_hard_state',
            'host_last_hard_state_change',
            'host_last_time_up',
            'host_last_time_down',
            'host_last_time_unreachable',
            'host_modified_host_attributes',
            'host',
            'service',
            'service_hard_state',
            'service_problem',
            'service_perfdata',
            'service_active_checks_enabled',
            'service_active_checks_enabled_changed',
            'service_passive_checks_enabled',
            'service_passive_checks_enabled_changed',
            'service_last_hard_state',
            'service_last_hard_state_change',
            'service_last_time_ok',
            'service_last_time_warning',
            'service_last_time_critical',
            'service_last_time_unknown',
            'service_check_execution_time',
            'service_check_latency',
            'service_current_check_attempt',
            'service_max_check_attempts',
            'service_obsessing',
            'service_obsessing_changed',
            'service_event_handler_enabled',
            'service_event_handler_enabled_changed',
            'service_flap_detection_enabled',
            'service_flap_detection_enabled_changed',
            'service_modified_service_attributes',
            'service_process_performance_data',
            'service_percent_state_change',
            'service_host_name'
        ))
            ->where('host_name', $this->host->getName())
            ->where('service_description', $this->service);
    }

    /**
     * Get the optional translated textual representation of a service state
     *
     * @param   int     $state
     * @param   bool    $translate
     *
     * @return  string
     * @throws  InvalidArgumentException If the service state is not valid
     */
    public static function getStateText($state, $translate = false)
    {
        $translate = (bool) $translate;
        switch ((int) $state) {
            case self::STATE_OK:
                $text = $translate ? mt('monitoring', 'ok') : 'ok';
                break;
            case self::STATE_WARNING:
                $text = $translate ? mt('monitoring', 'warning') : 'warning';
                break;
            case self::STATE_CRITICAL:
                $text = $translate ? mt('monitoring', 'critical') : 'critical';
                break;
            case self::STATE_UNKNOWN:
                $text = $translate ? mt('monitoring', 'unknown') : 'unknown';
                break;
            case self::STATE_PENDING:
                $text = $translate ? mt('monitoring', 'pending') : 'pending';
                break;
            default:
                throw new InvalidArgumentException('Invalid service state \'%s\'', $state);
        }
        return $text;
    }
}
