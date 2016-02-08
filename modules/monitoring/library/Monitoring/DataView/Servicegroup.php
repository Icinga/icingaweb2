<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Servicegroup extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'instance_name',
            'servicegroup_alias',
            'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'host', 'host_alias', 'host_display_name', 'host_name',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup'
        );
    }
}
