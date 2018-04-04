<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Data view for the host group summary
 */
class Hostgroupsummary extends DataView
{
    public function getColumns()
    {
        return array(
            'hostgroup_alias',
            'hostgroup_name',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_severity',
            'hosts_total',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_up',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_ok',
            'services_pending',
            'services_total',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_unhandled'
        );
    }

    public function getSearchColumns()
    {
        return array('hostgroup', 'hostgroup_alias');
    }

    public function getSortRules()
    {
        return array(
            'hostgroup_alias' => array(
                'order' => self::SORT_ASC
            ),
            'hosts_severity' => array(
                'columns' => array(
                    'hosts_severity',
                    'hostgroup_alias ASC'
                ),
                'order' => self::SORT_DESC
            )
        );
    }

    public function getStaticFilterColumns()
    {
        return array(
            'instance_name',
            'host', 'host_alias', 'host_display_name', 'host_name',
            'hostgroup',
            'service', 'service_description', 'service_display_name',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }
}
