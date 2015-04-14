<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
            'servicegroup_alias',
            'hostgroup_name',
            'hostgroup_alias',
            'hosts_up',
            'hosts_unreachable',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_down',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'services_total',
            'services_ok',
            'services_unknown',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_critical',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_warning',
            'services_warning_handled',
            'services_warning_unhandled',
            'services_pending',
            'services_severity',
            'services_ok_last_state_change',
            'services_pending_last_state_change',
            'services_warning_last_state_change_handled',
            'services_critical_last_state_change_handled',
            'services_unknown_last_state_change_handled',
            'services_warning_last_state_change_unhandled',
            'services_critical_last_state_change_unhandled',
            'services_unknown_last_state_change_unhandled'
        );
    }

    public function getSortRules()
    {
        return array(
            'services_severity' => array(
                'columns'   => array('services_severity'),
                'order'     => self::SORT_DESC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('hostgroup', 'servicegroup');
    }
}
