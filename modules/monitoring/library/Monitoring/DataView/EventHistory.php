<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
            'service_description',
            'object_type',
            'timestamp',
            'state',
            'attempt',
            'max_attempts',
            'output',
            'type',
            'host',
            'service',
            'service_host_name'
        );
    }

    public function getSortRules()
    {
        return array(
            'timestamp' => array(
                'columns' => array('timestamp'),
                'order' => 'DESC'
            )
        );
    }

    public function getFilterColumns()
    {
        return array(
            'hostgroups'
        );
    }
}
