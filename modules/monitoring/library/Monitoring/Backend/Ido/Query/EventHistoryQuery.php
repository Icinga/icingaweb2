<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use \Zend_Db_Select;
use Icinga\Exception\ProgrammingError;

class EventHistoryQuery extends AbstractQuery
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
            'timestamp'       => 'eh.timestamp',
            'raw_timestamp'   => 'eh.raw_timestamp',
            'state'           => 'eh.state',
//            'last_state'      => 'eh.last_state',
//            'last_hard_state' => 'eh.last_hard_state',
            'attempt'         => 'eh.attempt',
            'max_attempts'    => 'eh.max_attempts',
            'output'          => 'eh.output', // we do not want long_output
            //'problems'        => 'CASE WHEN eh.state = 0 OR eh.state IS NULL THEN 0 ELSE 1 END',
            'type'  => 'eh.type',
            'service_host_name'                => 'eho.name1 COLLATE latin1_general_ci',
            'service_description'             => 'eho.name2 COLLATE latin1_general_ci'
        ),
        'hostgroups' => array(
            'hostgroup' => 'hgo.name1 COLLATE latin1_general_ci',
        ),
    );

    protected $uglySlowConservativeCount = true;
    protected $maxCount = 1000;

    protected function joinBaseTables()
    {
//        $start = date('Y-m-d H:i:s', time() - 3600 * 24 * 1);
        $start = date('Y-m-d H:i:s', time() - 3600 * 24 * 2);
        $end   = date('Y-m-d H:i:s');
        // TODO: $this->dbTime(...)
        //$start = null;
        //$end  = null;
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
        if ($start) {
            foreach ($this->subQueries as $query) {
                $query->where('raw_timestamp', '>' . $start);
            }
        }
        if ($end) {
            foreach ($this->subQueries as $query) {
                $query->where('raw_timestamp', '<' . $end);
            }
        }
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
