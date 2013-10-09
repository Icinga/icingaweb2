<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\AbstractQuery as Query;

class Service extends AbstractObject
{
    protected $foreign = array(
        'servicegroups' => null,
        'contacts'      => null,
        'contactgroups' => null,
        'customvars'    => null,
        'comments'      => null,
    );

    public function stateName()
    {
        // TODO
    }

    protected function applyObjectFilter(Query $query)
    {
        return $query->where('service_host_name', $this->name1)
                     ->where('service_description', $this->name2);
    }

    public function prefetch()
    {
        return $this->fetchServicegroups()
            ->fetchContacts()
            ->fetchContactgroups()
            ->fetchCustomvars()
            ->fetchComments()
            ->fetchEventHistory();
    }

    protected function fetchObject()
    {
        return $this->backend->select()->from('status', array(
            'host_name',
            'host_alias',
            'host_address',
            'host_state',
            'host_handled',
            'host_in_downtime',
            'host_acknowledged',
            'host_last_state_change',
            'service_description',
            'service_state',
            'service_handled',
            'service_acknowledged',
            'service_in_downtime',
            'service_last_state_change',
            'last_check'    => 'service_last_check',
            'next_check'    => 'service_next_check',
            'check_execution_time'    => 'service_check_execution_time',
            'check_latency' => 'service_check_latency',
            'output'        => 'service_output',
            'long_output'   => 'service_long_output',
            'check_command' => 'service_check_command',
            'perfdata'      => 'service_perfdata',
            'current_check_attempt' => 'service_current_check_attempt',
            'max_check_attemt' => 'service_max_check_attempts',
            'state_type' => 'service_state_type',
            'passive_checks_enabled' => 'service_passive_checks_enabled',
            'last_state_change' => 'service_last_state_change',
            'last_notification' => 'service_last_notification',
            'current_notification_number' => 'service_current_notification_number',
            'is_flapping' => 'service_is_flapping',
            'percent_state_change' => 'service_percent_state_change',
            'in_downtime' => 'service_in_downtime',
            'passive_checks_enabled'    => 'service_passive_checks_enabled',
            'obsessing'                 => 'service_obsessing',
            'notifications_enabled'     => 'service_notifications_enabled',
            'event_handler_enabled'     => 'service_event_handler_enabled',
            'flap_detection_enabled'    => 'service_flap_detection_enabled',
            'active_checks_enabled'     => 'service_active_checks_enabled'
        ))
        ->where('host_name', $this->name1)
        ->where('service_description', $this->name2)
        ->fetchRow();
    }
}
