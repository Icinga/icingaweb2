<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Host and service downtimes view
 */
class Downtime extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'downtime_author_name',
            'downtime_comment',
            'downtime_duration',
            'downtime_end',
            'downtime_entry_time',
            'downtime_internal_id',
            'downtime_is_fixed',
            'downtime_is_flexible',
            'downtime_is_in_effect',
            'downtime_name',
            'downtime_scheduled_end',
            'downtime_scheduled_start',
            'downtime_start',
            'host_display_name',
            'host_name',
            'host_state',
            'object_type',
            'service_description',
            'service_display_name',
            'service_host_name',
            'service_state'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'downtime_author',
            'host', 'host_alias',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'instance_name',
            'service',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('host_display_name', 'service_display_name');
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'downtime_is_in_effect' => array(
                'columns' => array(
                    'downtime_is_in_effect',
                    'downtime_scheduled_start'
                ),
                'order' => self::SORT_DESC
            ),
            'downtime_start' => array(
                'order' => self::SORT_DESC
            ),
            'host_display_name' => array(
                'columns' => array(
                    'host_display_name',
                    'service_display_name'
                ),
                'order' => self::SORT_ASC
            ),
            'service_display_name' => array(
                'columns' => array(
                    'service_display_name',
                    'host_display_name'
                ),
                'order' => self::SORT_ASC
            )
        );
    }
}
