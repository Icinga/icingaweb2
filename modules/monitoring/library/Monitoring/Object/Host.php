<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use InvalidArgumentException;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

/**
 * An Icinga host
 */
class Host extends MonitoredObject
{
    /**
     * Host state 'UP'
     */
    const STATE_UP = 0;

    /**
     * Host state 'DOWN'
     */
    const STATE_DOWN = 1;

    /**
     * Host state 'UNREACHABLE'
     */
    const STATE_UNREACHABLE = 2;

    /**
     * Host state 'PENDING'
     */
    const STATE_PENDING = 99;

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
     * Hostname
     *
     * @var string
     */
    protected $host;

    /**
     * The services running on the hosts
     *
     * @var \Icinga\Module\Monitoring\Object\Service[]
     */
    protected $services;

    /**
     * Create a new host
     *
     * @param MonitoringBackend $backend    Backend to fetch host information from
     * @param string            $host       Hostname
     */
    public function __construct(MonitoringBackend $backend, $host)
    {
        parent::__construct($backend);
        $this->host = $host;
    }

    /**
     * Get the hostname
     *
     * @return string
     */
    public function getName()
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
        $columns = array(
            'host_acknowledged',
            'host_acknowledgement_type',
            'host_action_url',
            'host_active_checks_enabled',
            'host_active_checks_enabled_changed',
            'host_address',
            'host_address6',
            'host_alias',
            'host_attempt',
            'host_check_command',
            'host_check_execution_time',
            'host_check_latency',
            'host_check_source',
            'host_check_timeperiod',
            'host_current_check_attempt',
            'host_current_notification_number',
            'host_display_name',
            'host_event_handler_enabled',
            'host_event_handler_enabled_changed',
            'host_flap_detection_enabled',
            'host_flap_detection_enabled_changed',
            'host_handled',
            'host_icon_image',
            'host_icon_image_alt',
            'host_in_downtime',
            'host_is_flapping',
            'host_is_reachable',
            'host_last_check',
            'host_last_notification',
            'host_last_state_change',
            'host_long_output',
            'host_max_check_attempts',
            'host_name',
            'host_next_check',
            'host_next_update',
            'host_notes',
            'host_notes_url',
            'host_notifications_enabled',
            'host_notifications_enabled_changed',
            'host_obsessing',
            'host_obsessing_changed',
            'host_output',
            'host_passive_checks_enabled',
            'host_passive_checks_enabled_changed',
            'host_percent_state_change',
            'host_perfdata',
            'host_process_perfdata' => 'host_process_performance_data',
            'host_state',
            'host_state_type',
            'instance_name'
        );
        if ($this->backend->getType() === 'livestatus') {
            $columns[] = 'host_contacts';
        }
        return $this->backend->select()->from('hoststatus', $columns)
            ->where('host_name', $this->host);
    }

    /**
     * Fetch the services running on the host
     *
     * @return $this
     */
    public function fetchServices()
    {
        $services = array();
        foreach ($this->backend->select()->from('servicestatus', array('service_description'))
                ->where('host_name', $this->host)
                ->applyFilter($this->getFilter())
                ->getQuery() as $service) {
            $services[] = new Service($this->backend, $this->host, $service->service_description);
        }
        $this->services = $services;
        return $this;
    }

    /**
     * Get the optional translated textual representation of a host state
     *
     * @param   int     $state
     * @param   bool    $translate
     *
     * @return  string
     * @throws  InvalidArgumentException If the host state is not valid
     */
    public static function getStateText($state, $translate = false)
    {
        $translate = (bool) $translate;
        switch ((int) $state) {
            case self::STATE_UP:
                $text = $translate ? mt('monitoring', 'UP') : 'up';
                break;
            case self::STATE_DOWN:
                $text = $translate ? mt('monitoring', 'DOWN') : 'down';
                break;
            case self::STATE_UNREACHABLE:
                $text = $translate ? mt('monitoring', 'UNREACHABLE') : 'unreachable';
                break;
            case self::STATE_PENDING:
                $text = $translate ? mt('monitoring', 'PENDING') : 'pending';
                break;
            default:
                throw new InvalidArgumentException('Invalid host state \'%s\'', $state);
        }
        return $text;
    }

    public function getNotesUrls()
    {
        return $this->resolveAllStrings(
            MonitoredObject::parseAttributeUrls($this->host_notes_url)
        );
    }
}
