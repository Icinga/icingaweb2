<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use \Zend_Db_Select;

class EventHistoryQuery extends IdoQuery
{
    protected $subQueries = array();

    protected $columnMap = array(
        'eventhistory' => array(
            'cnt_notification'    => "SUM(CASE eh.type WHEN 'notify' THEN 1 ELSE 0 END)",
            'cnt_hard_state'      => "SUM(CASE eh.type WHEN 'hard_state' THEN 1 ELSE 0 END)",
            'cnt_soft_state'      => "SUM(CASE eh.type WHEN 'hard_state' THEN 1 ELSE 0 END)",
            'cnt_downtime_start'  => "SUM(CASE eh.type WHEN 'dt_start' THEN 1 ELSE 0 END)",
            'cnt_downtime_end'    => "SUM(CASE eh.type WHEN 'dt_end' THEN 1 ELSE 0 END)",
            'host'                => 'eho.name1 COLLATE latin1_general_ci',
            'service'             => 'eho.name2 COLLATE latin1_general_ci',
            'host_name'           => 'eho.name1 COLLATE latin1_general_ci',
            'service_description' => 'eho.name2 COLLATE latin1_general_ci',
            'object_type'         => "CASE WHEN eho.objecttype_id = 1 THEN 'host' ELSE 'service' END",
            'timestamp'           => 'eh.timestamp',
            'raw_timestamp'       => 'UNIX_TIMESTAMP(eh.raw_timestamp)',
            'state'               => 'eh.state',
            'attempt'             => 'eh.attempt',
            'max_attempts'        => 'eh.max_attempts',
            'output'              => 'eh.output', // we do not want long_output
            'type'                => 'eh.type',
            'service_host_name'   => 'eho.name1 COLLATE latin1_general_ci',
            'service_description' => 'eho.name2 COLLATE latin1_general_ci'
        ),
        'hostgroups' => array(
            'hostgroup' => 'hgo.name1 COLLATE latin1_general_ci',
        ),
    );

    protected $useSubqueryCount = true;

    protected function joinBaseTables()
    {
        $columns = array(
            'raw_timestamp',
            'timestamp',
            'object_id',
            'type',
            'output',
            'state',
            'state_type',
            'attempt',
            'max_attempts',
        );

        $this->subQueries = array(
            $this->createSubQuery('Statehistory', $columns),
            $this->createSubQuery('Downtimestarthistory', $columns),
            $this->createSubQuery('Downtimeendhistory', $columns),
            $this->createSubQuery('Commenthistory', $columns),
            $this->createSubQuery('Notificationhistory', $columns)
        );

        $sub = $this->db->select()->union($this->subQueries, Zend_Db_Select::SQL_UNION_ALL);
        $this->baseQuery = $this->db->select()->from(
            array('eho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('eh' => $sub),
            'eho.' . $this->object_id
            . ' = eh.' . $this->object_id
            . ' AND eho.is_active = 1',
            array()
        );
        $this->joinedVirtualTables = array('eventhistory' => true);
    }

    protected function joinHostgroups()
    {
        $this->baseQuery->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = eho.object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            "hgm.hostgroup_id = hg.$this->hostgroup_id",
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.' . $this->object_id. ' = hg.hostgroup_object_id'
          . ' AND hgo.is_active = 1',
            array()
        );

        return $this;
    }

}
