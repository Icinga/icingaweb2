<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class DowntimeendhistoryQuery extends AbstractQuery
{
    protected $columnMap = array(
        'downtimehistory' => array(
            'state_time'    => 'actual_end_time',
            'timestamp'     => 'UNIX_TIMESTAMP(actual_end_time)',
            'raw_timestamp' => 'actual_end_time',
            'object_id'     => 'object_id',
            'type'          => "('dt_end')",
            'state'         => '(NULL)',
            'state_type'    => '(NULL)',
            'output'        => "('[' || author_name || '] ' || comment_data)",
            'attempt'       => '(NULL)',
            'max_attempts'  => '(NULL)',
        )
    );
}

