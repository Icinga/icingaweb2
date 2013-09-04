<?php

namespace Icinga\Module\Monitoring\View;

class ContactView extends MonitoringView
{
    protected $query;

    protected $availableColumns = array(
        'contact_alias',
        'contact_email',
        'contact_pager',
        'contact_notify_hosts',
        'contact_notify_services',
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'contact_alias' => array(
            'default_dir' => self::SORT_ASC
        )
    );
}
