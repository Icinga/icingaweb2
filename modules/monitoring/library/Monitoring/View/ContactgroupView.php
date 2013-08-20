<?php

namespace Icinga\Module\Monitoring\View;

class ContactgroupView extends MonitoringView
{
    protected $query;

    protected $availableColumns = array(
        'contactgroup_name',
        'contactgroup_alias',
        'host_name',
        'service_description'
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'contactgroup_alias' => array(
            'default_dir' => self::SORT_ASC
        )
    );
}
