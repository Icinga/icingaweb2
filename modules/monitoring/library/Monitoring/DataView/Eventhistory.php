<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class EventHistory extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'instance_name',
            'cnt_notification',
            'cnt_hard_state',
            'cnt_soft_state',
            'cnt_downtime_start',
            'cnt_downtime_end',
            'host_name',
            'host_display_name',
            'service_description',
            'service_display_name',
            'object_type',
            'timestamp',
            'state',
            'output',
            'type'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'timestamp' => array(
                'order' => self::SORT_DESC
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'host', 'host_alias',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('host_display_name', 'service_display_name');
    }
}
