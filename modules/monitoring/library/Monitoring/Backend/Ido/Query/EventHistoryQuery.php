<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for event history
 */
class EventHistoryQuery extends IdoQuery
{
    /**
     * Subqueries used for the event history query
     *
     * @type    IdoQuery[]
     *
     * @see     EventHistoryQuery::joinBaseTables() For the used subqueries.
     */
    protected $subQueries = array();

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'eventhistory' => array(
            'cnt_notification'      => "SUM(CASE eh.type WHEN 'notify' THEN 1 ELSE 0 END)",
            'cnt_hard_state'        => "SUM(CASE eh.type WHEN 'hard_state' THEN 1 ELSE 0 END)",
            'cnt_soft_state'        => "SUM(CASE eh.type WHEN 'hard_state' THEN 1 ELSE 0 END)",
            'cnt_downtime_start'    => "SUM(CASE eh.type WHEN 'dt_start' THEN 1 ELSE 0 END)",
            'cnt_downtime_end'      => "SUM(CASE eh.type WHEN 'dt_end' THEN 1 ELSE 0 END)",
            'host'                  => 'eho.name1 COLLATE latin1_general_ci',
            'service'               => 'eho.name2 COLLATE latin1_general_ci',
            'host_name'             => 'eho.name1',
            'service_description'   => 'eho.name2',
            'object_type'           => 'eh.object_type',
            'timestamp'             => 'eh.timestamp',
            'state'                 => 'eh.state',
            'attempt'               => 'eh.attempt',
            'max_attempts'          => 'eh.max_attempts',
            'output'                => 'eh.output', // we do not want long_output
            'type'                  => 'eh.type'
        ),
        'hostgroups' => array(
            'hostgroup'             => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_name'        => 'hgo.name1'
        ),
        'hosts' => array(
            'host_display_name'     => 'CASE WHEN sh.display_name IS NOT NULL THEN sh.display_name ELSE h.display_name END'
        ),
        'services' => array(
            'service_display_name'  => 's.display_name'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected $useSubqueryCount = true;

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $columns = array(
            'timestamp',
            'object_id',
            'type',
            'output',
            'state',
            'state_type',
            'object_type',
            'attempt',
            'max_attempts',
        );
        $this->subQueries = array(
            $this->createSubQuery('Statehistory', $columns),
            $this->createSubQuery('Downtimestarthistory', $columns),
            $this->createSubQuery('Downtimeendhistory', $columns),
            $this->createSubQuery('Commenthistory', $columns),
            $this->createSubQuery('Commentdeletionhistory', $columns),
            $this->createSubQuery('Notificationhistory', $columns)
        );
        $sub = $this->db->select()->union($this->subQueries, Zend_Db_Select::SQL_UNION_ALL);

        $this->select->from(
            array('eho' => $this->prefix . 'objects'),
            '*'
        )->join(
            array('eh' => $sub),
            'eho.' . $this->object_id . ' = eh.' . $this->object_id . ' AND eho.is_active = 1',
            array()
        );
        $this->joinedVirtualTables = array('eventhistory' => true);
    }

    /**
     * {@inheritdoc}
     */
    public function order($columnOrAlias, $dir = null)
    {
        foreach ($this->subQueries as $sub) {
            $sub->requireColumn($columnOrAlias);
        }
        return parent::order($columnOrAlias, $dir);
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        $this->requireColumn($condition);
        foreach ($this->subQueries as $sub) {
            $sub->where($condition, $value);
        }
        return $this;
    }

    /**
     * Join host groups
     *
     * @return $this
     */
    protected function joinHostgroups()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = eho.object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hgm.hostgroup_id = hg.' . $this->hostgroup_id,
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.' . $this->object_id. ' = hg.hostgroup_object_id' . ' AND hgo.is_active = 1',
            array()
        );
        return $this;
    }

    /**
     * Join hosts
     *
     * @return $this
     */
    protected function joinHosts()
    {
        $this->select->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = eho.object_id',
            array()
        );
        return $this;
    }

    /**
     * Join services
     *
     * @return $this
     */
    protected function joinServices()
    {
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = eho.object_id',
            array()
        );
        $this->select->joinLeft(
            array('sh' => $this->prefix . 'hosts'),
            'sh.host_object_id = s.host_object_id',
            array()
        );
        return $this;
    }
}
