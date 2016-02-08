<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Eventgrid extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'day',
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
            'host_display_name',
            'service_description',
            'service_display_name',
            'timestamp'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'day' => array(
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
            'instance_name',
            'host', 'host_alias',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_host_name',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }
}
