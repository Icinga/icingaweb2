<?php

namespace Icinga\Monitoring\View;

use Icinga\Module\Monitoring\View\AbstractView;

class StatehistoryView extends AbstractView
{
    protected $availableColumns = array(
        'raw_timestamp',
        'timestamp',
        'state',
        'attempt',
        'max_attempts',
        'output',
        'type'
    );

    protected $sortDefaults = array(
        'timestamp' => array(
            'columns' => array('raw_timestamp'),
            'default_dir' => self::SORT_DESC
        ),
    );
}
