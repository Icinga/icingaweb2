<?php

namespace Icinga\Monitoring\Backend\Ido\Query;

class CommenthistoryQuery extends AbstractQuery
{
    protected $columnMap = array(
        'commenthistory' => array(
            'state_time'    => 'comment_time',
            'timestamp'     => 'UNIX_TIMESTAMP(comment_time)',
            'raw_timestamp' => 'comment_time',
            'object_id'     => 'object_id',
            'type'          => "(CASE entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'dt_comment' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END)",
            'state'         => '(NULL)',
            'state_type'    => '(NULL)',
            'output'        => "('[' || author_name || '] ' || comment_data)",
            'attempt'       => '(NULL)',
            'max_attempts'  => '(NULL)',
        )
    );
}

