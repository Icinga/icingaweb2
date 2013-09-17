<?php

namespace Icinga\Module\Monitoring\View;

class ServicegroupView extends AbstractView
{
    protected $query;

    protected $availableColumns = array(
        'servicegroup_name',
        'servicegroup_alias',
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'servicegroup_alias' => array(
            'default_dir' => self::SORT_ASC
        )
    );
}
