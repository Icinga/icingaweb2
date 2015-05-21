<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Servicegroupsummary extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_up',
            'servicegroup_alias',
            'servicegroup_name',
            'services_critical_handled',
            'services_critical_last_state_change_handled',
            'services_critical_last_state_change_unhandled',
            'services_critical_unhandled',
            'services_ok',
            'services_ok_last_state_change',
            'services_pending',
            'services_pending_last_state_change',
            'services_severity',
            'services_total',
            'services_unknown_handled',
            'services_unknown_last_state_change_handled',
            'services_unknown_last_state_change_unhandled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_last_state_change_handled',
            'services_warning_last_state_change_unhandled',
            'services_warning_unhandled'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterColumns()
    {
        return array('servicegroup');
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
        return array('servicegroup', 'servicegroup_alias');
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'servicegroup_alias' => array(
                'order' => self::SORT_ASC
            ),
            'services_severity' => array(
                'columns' => array(
                    'services_severity',
                    'servicegroup_alias ASC'
                ),
                'order' => self::SORT_DESC
            ),
            'services_total' => array(
                'columns' => array(
                    'services_total',
                    'servicegroup_alias ASC'
                ),
                'order' => self::SORT_ASC
            )
        );
    }
}
