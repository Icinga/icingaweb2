<?php

namespace Monitoring\Backend\Livestatus\Query;

use Icinga\Data\AbstractQuery;

class StatusQuery extends AbstractQuery
{
    protected $available_columns = array(
        'host_name',
        'host_display_name',
        'host_alias',
        'host_address',
        'host_ipv4'                      => 'host_address', // TODO
        'host_icon_image',

        // Host state
        'host_state',
        'host_output'                    => 'host_plugin_output',
        'host_perfdata'                  => 'host_perf_data',
        'host_acknowledged',
        'host_does_active_checks'        => 'host_active_checks_enabled',
        'host_accepts_passive_checks'     => 'host_accept_passive_checks',
        'host_last_state_change',

        'host_problems' => 'is_flapping',
        'service_in_downtime' => 'is_flapping',
        'service_handled' => 'is_flapping',

        // Service config
        'service_description'            => 'description',
        'service_display_name'           => 'display_name',

        // Service state
        'service_state'                  => 'state',
        'service_output'                 => 'plugin_output',
        'service_perfdata'               => 'perf_data',
        'service_acknowledged'           => 'acknowledged',
        'service_does_active_checks'     => 'active_checks_enabled',
        'service_accepts_passive_checks' => 'accept_passive_checks',
        'service_last_state_change'      => 'last_state_change',

        // Service comments
        //'comments_with_info',
        //'downtimes_with_info',
    );

    public function init()
    {
        $this->query = $this->createQuery();
        //print_r($this->ds->getConnection()->fetchAll($this->query));
        //die('asdf');
    }

    public function count()
    {
        return $this->ds->getConnection()->count($this->query);
    }

    public function fetchAll()
    {
        return $this->ds->getConnection()->fetchAll($this->query);
    }

    public function fetchRow()
    {
        return array_shift($this->ds->getConnection()->fetchAll($this->query));
    }

    protected function createQuery()
    {
        return $this->ds->getConnection()->select()->from('services', $this->available_columns);
    }
}
