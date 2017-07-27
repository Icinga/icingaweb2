<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Servicegroup extends DataView
{
    public function getColumns()
    {
        return array(
            'servicegroup_alias',
            'servicegroup_name'
        );
    }

    public function getSortRules()
    {
        return array(
            'servicegroup_alias' => array(
                'order' => self::SORT_ASC
            )
        );
    }

    public function getStaticFilterColumns()
    {
        return array(
            'instance_name', 'host_name', 'hostgroup_name', 'service_description'
        );
    }
}
