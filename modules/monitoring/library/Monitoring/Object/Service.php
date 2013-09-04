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
            ;
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
        ))
        ->where('host_name', $this->name1)
        ->where('service_description', $this->name2)
        ->fetchRow();
    }
}
