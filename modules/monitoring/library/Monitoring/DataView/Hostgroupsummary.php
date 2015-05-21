<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Data view for the host group summary
 */
class Hostgroupsummary extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'hostgroup_alias',
            'hostgroup_name',
            'hosts_down_handled',
            'hosts_down_last_state_change_handled',
            'hosts_down_last_state_change_unhandled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_pending_last_state_change',
            'hosts_severity',
            'hosts_total',
            'hosts_unreachable_handled',
            'hosts_unreachable_last_state_change_handled',
            'hosts_unreachable_last_state_change_unhandled',
            'hosts_unreachable_unhandled',
            'hosts_up',
            'hosts_up_last_state_change',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_ok',
            'services_pending',
            'services_total',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_unhandled'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterColumns()
    {
        return array('hostgroup');
    }

    /**
     * {@inheritdoc}
     */
    public static function getQueryName()
    {
        return 'groupsummary';
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('hostgroup', 'hostgroup_alias');
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'hostgroup_alias' => array(
                'order' => self::SORT_ASC
            ),
            'hosts_severity' => array(
                'columns' => array(
                    'hosts_severity',
                    'hostgroup_alias ASC'
                ),
                'order' => self::SORT_DESC
            ),
            'hosts_total' => array(
                'columns' => array(
                    'hosts_total',
                    'hostgroup_alias ASC'
                ),
                'order' => self::SORT_ASC
            ),
            'services_total' => array(
                'columns' => array(
                    'services_total',
                    'hostgroup_alias ASC'
                ),
                'order' => self::SORT_ASC
            )
        );
    }
}
