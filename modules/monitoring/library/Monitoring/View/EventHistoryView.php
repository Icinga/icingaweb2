<?php

namespace Icinga\Module\Monitoring\View;

class EventHistoryView extends MonitoringView
{
    protected $query;

    protected $availableColumns = array(
        'raw_timestamp',
        'timestamp',
        'host',
        'service',
        'host_name',
        'state',
        'last_state',
        'last_hard_state',
        'attempt',
        'max_attempts',
        'output',
        'type'
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'raw_timestamp' => array(
            'default_dir' => self::SORT_DESC
        ),
        'timestamp' => array(
            'default_dir' => self::SORT_DESC
        ),
    );
}
