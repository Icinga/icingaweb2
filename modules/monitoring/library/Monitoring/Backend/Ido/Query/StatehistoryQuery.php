<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class StatehistoryQuery extends IdoQuery
{
    protected $types = array(
        'soft_state' => 0,
        'hard_state' => 1
    );

    protected $columnMap = array(
        'statehistory' => array(
            'state_time'            => 'sh.state_time',
            'timestamp'             => 'UNIX_TIMESTAMP(sh.state_time)',
            'raw_timestamp'         => 'sh.state_time',
            'object_id'             => 'sho.object_id',
            'type'                  => "(CASE WHEN sh.state_type = 1 THEN 'hard_state' ELSE 'soft_state' END)",
            'state'                 => 'sh.state',
            'state_type'            => 'sh.state_type',
            'output'                => 'sh.output',
            'attempt'               => 'sh.current_check_attempt',
            'max_attempts'          => 'sh.max_check_attempts',

            'host'                  => 'sho.name1 COLLATE latin1_general_ci',
            'service'               => 'sho.name2 COLLATE latin1_general_ci',
            'host_name'             => 'sho.name1',
            'service_description'   => 'sho.name2',
            'object_type'           => "CASE WHEN sho.objecttype_id = 1 THEN 'host' ELSE 'service' END"
        )
    );

    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(sh.state_time)') {
            return 'sh.state_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } elseif ($col === $this->columnMap['statehistory']['type']
            && is_array($expression) === false
            && array_key_exists($expression, $this->types) === true
        ) {
                return 'sh.state_type ' . $sign . ' ' . $this->types[$expression];
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }

    protected function joinBaseTables()
    {
        $this->select->from(
            array('sho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('sh' => $this->prefix . 'statehistory'),
            'sho.' . $this->object_id . ' = sh.' . $this->object_id . ' AND sho.is_active = 1',
            array()
        );
        $this->joinedVirtualTables = array('statehistory' => true);
    }
}
