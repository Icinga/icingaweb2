<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Data view for host status summaries
 */
class Hoststatussummary extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_total',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_up',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'instance_name',
            'host', 'host_alias', 'host_display_name', 'host_name',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }
}
