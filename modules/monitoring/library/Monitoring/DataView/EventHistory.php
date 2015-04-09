<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class EventHistory extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'cnt_notification',
            'cnt_hard_state',
            'cnt_soft_state',
            'cnt_downtime_start',
            'cnt_downtime_end',
            'host_name',
            'host_display_name',
            'service_description',
            'service_display_name',
            'hostgroup_name',
            'object_type',
            'timestamp',
            'state',
            'attempt',
            'max_attempts',
            'output',
            'type'
        );
    }

    public function getSortRules()
    {
        return array(
            'timestamp' => array(
                'columns'   => array('timestamp'),
                'order'     => 'DESC'
            )
        );
    }

    public function getFilterColumns()
    {
        return array('host', 'service', 'hostgroup');
    }
}
