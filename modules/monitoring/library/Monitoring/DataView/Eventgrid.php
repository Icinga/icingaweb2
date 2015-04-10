<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Eventgrid extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'day',
            'cnt_events',
            'objecttype_id',
            'cnt_up',
            'cnt_down_hard',
            'cnt_down',
            'cnt_unreachable_hard',
            'cnt_unreachable',
            'cnt_unknown_hard',
            'cnt_unknown',
            'cnt_critical',
            'cnt_critical_hard',
            'cnt_warning',
            'cnt_warning_hard',
            'cnt_ok',
            'host_name',
            'service_description',
            'timestamp',
            'servicegroup_name',
            'hostgroup_name'
        );
    }

    public function getSortRules()
    {
        return array(
            'day' => array(
                'order' => self::SORT_DESC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('host', 'service', 'hostgroup', 'servicegroup');
    }
}
