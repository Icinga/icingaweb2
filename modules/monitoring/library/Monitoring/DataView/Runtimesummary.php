<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * View for runtimesummary query
 */
class Runtimesummary extends DataView
{
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
