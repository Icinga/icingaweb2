<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Host group data view
 */
class Hostgroup extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'hostgroup_alias',
            'hostgroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'hostgroup_alias' => array(
                'order' => self::SORT_ASC
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'instance_name', 'host_name', 'service_description', 'servicegroup_name'
        );
    }
}
