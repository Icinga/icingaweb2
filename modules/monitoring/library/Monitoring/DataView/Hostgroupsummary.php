<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

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
            'hosts_down_handled_last_state_change',
            'hosts_down_unhandled',
            'hosts_down_unhandled_last_state_change',
            'hosts_pending',
            'hosts_pending_last_state_change',
            'hosts_total',
            'hosts_unreachable_handled',
            'hosts_unreachable_handled_last_state_change',
            'hosts_unreachable_unhandled',
            'hosts_unreachable_unhandled_last_state_change',
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
    public function getStaticFilterColumns()
    {
        return array(
            'instance_name',
            'hosts_severity',
            'host', 'host_alias', 'host_display_name', 'host_name',
            'hostgroup',
            'service', 'service_description', 'service_display_name',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('hostgroup_alias');
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
