<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Object;

use InvalidArgumentException;
use Icinga\Module\Monitoring\Backend;

/**
 * A Icinga host
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
     * Host name
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

    /**
     * Fetch the services running on the host
     *
     * @return $this
     */
    public function fetchServices()
    {
        $services = array();
        foreach ($this->backend->select()->from('serviceStatus', array('service_description'))
                ->where('host_name', $this->host)
                ->getQuery()
                ->fetchAll() as $service) {
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
                $text = $translate ? mt('monitoring', 'up') : 'up';
                break;
            case self::STATE_DOWN:
                $text = $translate ? mt('monitoring', 'down') : 'down';
                break;
            case self::STATE_UNREACHABLE:
                $text = $translate ? mt('monitoring', 'unreachable') : 'unreachable';
                break;
            case self::STATE_PENDING:
                $text = $translate ? mt('monitoring', 'pending') : 'pending';
                break;
            default:
                throw new InvalidArgumentException('Invalid host state \'%s\'', $state);
        }
        return $text;
    }
}
