<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Data\AbstractQuery as Query;

class Host extends AbstractObject
{
    protected $foreign = array(
        'hostgroups'    => null,
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
        return $query->where('host_name', $this->name1);
    }

    public function prefetch()
    {
        return $this->fetchHostgroups()
            ->fetchContacts()
            ->fetchContactgroups()
            ->fetchCustomvars()
            ->fetchComments();
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
            'last_check'    => 'host_last_check',
            'next_check'    => 'host_next_check',
            'check_execution_time'    => 'host_check_execution_time',
            'check_latency' => 'host_check_latency',
            'output'        => 'host_output',
            'long_output'   => 'host_long_output',
            'check_command' => 'host_check_command',
            'perfdata'      => 'host_perfdata',
        ))->where('host_name', $this->name1)->fetchRow();
    }
}
