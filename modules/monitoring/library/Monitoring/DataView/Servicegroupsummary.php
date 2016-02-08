<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Data view for service group summaries
 */
class Servicegroupsummary extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'servicegroup_alias',
            'servicegroup_name',
            'services_critical_handled',
            'services_critical_handled_last_state_change',
            'services_critical_unhandled',
            'services_critical_unhandled_last_state_change',
            'services_ok',
            'services_ok_last_state_change',
            'services_pending',
            'services_pending_last_state_change',
            'services_total',
            'services_unknown_handled',
            'services_unknown_handled_last_state_change',
            'services_unknown_unhandled',
            'services_unknown_unhandled_last_state_change',
            'services_warning_handled',
            'services_warning_handled_last_state_change',
            'services_warning_unhandled',
            'services_warning_unhandled_last_state_change'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'instance_name',
            'services_severity',
            'host', 'host_alias', 'host_display_name', 'host_name',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('servicegroup_alias');
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
