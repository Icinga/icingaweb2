<?php

namespace Icinga\Monitoring\View;

class CommentView extends MonitoringView
{
    protected $query;

    protected $availableColumns = array(
        'comment_data',
        'comment_author',
        'comment_timestamp',
        'comment_type',
    );

    protected $specialFilters = array();

    protected $sortDefaults = array(
        'comment_timestamp' => array(
            'default_dir' => self::SORT_DESC
        )
    );
}
