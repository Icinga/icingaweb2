<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Data view for service group summaries
 */
class Servicegroupsummary extends DataView
{
    public function getColumns()
    {
        return array(
            'servicegroup_alias',
            'servicegroup_name',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_ok',
            'services_pending',
            'services_severity',
            'services_total',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_unhandled'
        );
    }

    public function getSearchColumns()
    {
        return array('servicegroup', 'servicegroup_alias');
    }

    public function getSortRules()
    {
        return array(
            'servicegroup_alias' => array(
                'order' => self::SORT_ASC
            ),
            'services_severity' => array(
                'columns' => array(
                    'services_severity',
                    'servicegroup_alias ASC'
                ),
                'order' => self::SORT_DESC
            )
        );
    }

    public function getStaticFilterColumns()
    {
        return array(
            'instance_name',
            'services_severity',
            'host', 'host_alias', 'host_display_name', 'host_name',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup'
        );
    }
}
