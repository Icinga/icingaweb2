<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Data view for unhandled service problems
 */
class Unhandledserviceproblems extends DataView
{
    public function getColumns()
    {
        return array(
            'services_critical_unhandled'
        );
    }

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
