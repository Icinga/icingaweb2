<?php

namespace Icinga\Module\Monitoring\View;

class HostgroupView extends AbstractView
{
    protected $query;

    protected $availableColumns = array(
        'hostgroup_name',
        'hostgroup_alias',
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'hostgroup_alias' => array(
            'default_dir' => self::SORT_ASC
        )
    );
}
