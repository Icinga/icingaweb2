<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

class EventHistory extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'raw_timestamp',
            'timestamp',
            'host',
            'service',
            'host_name',
            'state',
            'attempt',
            'max_attempts',
            'output',
            'type'
        );
    }

    public function getSortRules()
    {
        return array(
            'raw_timestamp' => array(
                'default_dir' => self::SORT_DESC
            ),
            'timestamp' => array(
                'default_dir' => self::SORT_DESC
            )
        );
    }

    public function getFilterColumns()
    {
        return array(
            'hostgroups'
        );
    }
}
