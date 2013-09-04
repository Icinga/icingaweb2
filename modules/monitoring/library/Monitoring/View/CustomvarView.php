<?php

namespace Icinga\Module\Monitoring\View;

class CustomvarView extends MonitoringView
{
    protected $query;

    protected $availableColumns = array(
        'varname',
        'varvalue',
        'object_type',
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'varname' => array(
            'varname'  => self::SORT_ASC,
            'varvalue' => self::SORT_ASC,
        )
    );
}
