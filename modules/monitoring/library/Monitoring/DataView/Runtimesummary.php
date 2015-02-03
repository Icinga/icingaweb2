<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

/**
 * View for runtimesummary query
 */
class Runtimesummary extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'check_type',
            'active_checks_enabled',
            'passive_checks_enabled',
            'execution_time',
            'latency',
            'object_count',
            'object_type'
        );
    }

    /**
     * Retrieve default sorting rules for particular columns. These involve sort order and potential additional to sort
     *
     * @return array
     */
    public function getSortRules()
    {
        return array(
            'active_checks_enabled' => array(
                'order' => self::SORT_ASC
            )
        );
    }
}
