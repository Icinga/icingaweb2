<?php

namespace Icinga\Monitoring\Backend\Ido\Query;

class DowntimestarthistoryQuery extends AbstractQuery
{
    protected $columnMap = array(
        'downtimehistory' => array(
            'state_time'    => 'actual_start_time',
            'timestamp'     => 'UNIX_TIMESTAMP(actual_start_time)',
            'raw_timestamp' => 'actual_start_time',
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

