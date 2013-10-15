<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

class Groupsummary extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'servicegroup_name',
            'hostgroup_name',
            'cnt_hosts_up',
            'cnt_hosts_unreachable',
            'cnt_hosts_unreachable_unhandled',
            'cnt_hosts_down',
            'cnt_hosts_down_unhandled',
            'cnt_hosts_pending',
            'cnt_services_ok',
            'cnt_services_unknown',
            'cnt_services_unknown_unhandled',
            'cnt_services_critical',
            'cnt_services_critical_unhandled',
            'cnt_services_warning',
            'cnt_services_warning_unhandled',
            'cnt_services_pending'
        );
    }

    public function getSortRules()
    {
        return array(
            'servicegroup_name' => array(
                'order' => self::SORT_ASC
            ),
            'hostgroup_name' => array(
                'order' => self::SORT_ASC
            )
        );
    }
}
