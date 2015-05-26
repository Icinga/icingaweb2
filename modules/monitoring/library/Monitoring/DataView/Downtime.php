<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Downtime extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function isValidFilterTarget($column)
    {
        if ($column[0] === '_'
            && preg_match('/^_(?:host|service)_/', $column)
        ) {
            return true;
        }
        return parent::isValidFilterTarget($column);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'downtime_objecttype',
            'downtime_author_name',
            'downtime_comment',
            'downtime_entry_time',
            'downtime_is_fixed',
            'downtime_is_flexible',
            'downtime_start',
            'downtime_scheduled_start',
            'downtime_scheduled_end',
            'downtime_end',
            'downtime_duration',
            'downtime_is_in_effect',
            'downtime_triggered_by_id',
            'downtime_internal_id',
            'downtime_host_state',
            'downtime_service_state',
            'host_display_name',
            'service_display_name',
            'host_name',
            'service_host_name',
            'service_description'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterColumns()
    {
        return array('hostgroup', 'hostgroup_alias', 'hostgroup_name', 'servicegroup', 'servicegroup_alias', 'servicegroup_name');
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
