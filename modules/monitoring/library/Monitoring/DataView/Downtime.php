<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
            'host_name',
            'object_type',
            'service_host_name',
            'service_description',
            'downtime_type',
            'downtime_author_name',
            'downtime_comment_data',
            'downtime_is_fixed',
            'downtime_duration',
            'downtime_entry_time',
            'downtime_scheduled_start_time',
            'downtime_scheduled_end_time',
            'downtime_was_started',
            'downtime_actual_start_time',
            'downtime_actual_start_time_usec',
            'downtime_is_in_effect',
            'downtime_trigger_time',
            'downtime_triggered_by_id',
            'downtime_internal_downtime_id'
        );
    }

    public function getSortRules()
    {
        return array(
            'downtime_is_in_effect' => array(
                'order' => self::SORT_DESC
            ),
            'downtime_actual_start_time' => array(
                'order' => self::SORT_DESC
            )
        );
    }
}
