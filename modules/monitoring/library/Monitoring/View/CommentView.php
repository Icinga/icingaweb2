<?php

namespace Icinga\Module\Monitoring\View;

class CommentView extends MonitoringView
{
    protected $query;

    protected $availableColumns = array(
        'comment_data',
        'comment_author',
        'comment_timestamp',
        'comment_type',
        'host_name',
        'service_host_name',
        'service_description',
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'comment_timestamp' => array(
            'default_dir' => self::SORT_DESC
        )
    );
}
