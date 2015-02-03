<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Monitoring\DataView;

class Downtime extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'downtime_objecttype',
            'downtime_author',
            'author',
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
            'downtime_host',
            'downtime_service',
            'downtime_host_state',
            'downtime_service_state',
            'host_display_name',
            'service_display_name'
        );
    }

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
