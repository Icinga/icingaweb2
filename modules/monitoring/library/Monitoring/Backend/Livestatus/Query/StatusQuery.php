<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace \Icinga\Module\Monitoring\Backend\Livestatus\Query;

use Icinga\Data\SimpleQuery;

class StatusQuery extends SimpleQuery implements Filterable
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
        'comments_with_info',
        'downtimes_with_info',
    );

    protected function createQuery()
    {
        return $this->connection->getConnection()->select()->from('services', $this->available_columns);
    }
}
