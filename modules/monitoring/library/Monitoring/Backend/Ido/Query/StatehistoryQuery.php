<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class StatehistoryQuery extends AbstractQuery
{
    protected $columnMap = array(
        'statehistory' => array(
            'raw_timestamp' => 'state_time',
            'timestamp'  => 'UNIX_TIMESTAMP(state_time)',
            'state_time' => 'state_time',
            'object_id'  => 'object_id',
            'type'       => "(CASE WHEN state_type = 1 THEN 'hard_state' ELSE 'soft_state' END)",
            'state'      => 'state',
            'state_type' => 'state_type',
            'output'     => 'output',
            'attempt'      => 'current_check_attempt',
            'max_attempts' => 'max_check_attempts',
        )
    );
}

