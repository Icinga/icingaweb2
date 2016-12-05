<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use InvalidArgumentException;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

/**
 * An Icinga service
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
     * @param MonitoringBackend $backend    Backend to fetch service information from
     * @param string            $host       Hostname the service is running on
     * @param string            $service    Service name
     */
    public function __construct(MonitoringBackend $backend, $host, $service)
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
        return $this->backend->select()->from('servicestatus', array(
            'instance_name',
            'host_attempt',
            'host_icon_image',
            'host_icon_image_alt',
            'host_acknowledged',
            'host_active_checks_enabled',
            'host_address',
            'host_address6',
            'host_alias',
            'host_display_name',
            'host_handled',
            'host_in_downtime',
            'host_is_flapping',
            'host_last_state_change',
            'host_name',
            'host_notifications_enabled',
            'host_passive_checks_enabled',
            'host_state',
            'host_state_type',
            'service_icon_image',
            'service_icon_image_alt',
            'service_acknowledged',
            'service_acknowledgement_type',
            'service_action_url',
            'service_active_checks_enabled',
            'service_active_checks_enabled_changed',
            'service_attempt',
            'service_check_command',
            'service_check_execution_time',
            'service_check_latency',
            'service_check_source',
            'service_check_timeperiod',
            'service_current_notification_number',
            'service_description',
            'service_display_name',
            'service_event_handler_enabled',
            'service_event_handler_enabled_changed',
            'service_flap_detection_enabled',
            'service_flap_detection_enabled_changed',
            'service_handled',
            'service_in_downtime',
            'service_is_flapping',
            'service_is_reachable',
            'service_last_check',
            'service_last_notification',
            'service_last_state_change',
            'service_long_output',
            'service_next_check',
            'service_next_update',
            'service_notes',
            'service_notes_url',
            'service_notifications_enabled',
            'service_notifications_enabled_changed',
            'service_obsessing',
            'service_obsessing_changed',
            'service_output',
            'service_passive_checks_enabled',
            'service_passive_checks_enabled_changed',
            'service_percent_state_change',
            'service_perfdata',
            'service_process_perfdata' => 'service_process_performance_data',
            'service_state',
            'service_state_type'
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
                $text = $translate ? mt('monitoring', 'OK') : 'ok';
                break;
            case self::STATE_WARNING:
                $text = $translate ? mt('monitoring', 'WARNING') : 'warning';
                break;
            case self::STATE_CRITICAL:
                $text = $translate ? mt('monitoring', 'CRITICAL') : 'critical';
                break;
            case self::STATE_UNKNOWN:
                $text = $translate ? mt('monitoring', 'UNKNOWN') : 'unknown';
                break;
            case self::STATE_PENDING:
                $text = $translate ? mt('monitoring', 'PENDING') : 'pending';
                break;
            default:
                throw new InvalidArgumentException('Invalid service state \'%s\'', $state);
        }
        return $text;
    }

    public function getNotesUrls()
    {
        return $this->resolveAllStrings(
            MonitoredObject::parseAttributeUrls($this->service_notes_url)
        );
    }
}
