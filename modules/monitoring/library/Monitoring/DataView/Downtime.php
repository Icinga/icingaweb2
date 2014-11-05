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
            'downtime_objecttype',
            'downtime_author',
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
            'downtime_service_state'
        );
    }

    public function getSortRules()
    {
        return array(
            'downtime_is_in_effect' => array(
                'order' => self::SORT_DESC
            ),
            'downtime_start' => array(
                'order' => self::SORT_DESC
            ),
            'downtime_host' => array(
                'columns' => array(
                    'downtime_host',
                    'downtime_service'
                ),
                'order' => self::SORT_ASC
            ),
        );
    }
}
