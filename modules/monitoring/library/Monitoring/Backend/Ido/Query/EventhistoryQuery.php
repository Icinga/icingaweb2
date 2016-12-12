<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for event history records
 */
class EventhistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $useSubqueryCount = true;

    /**
     * Subqueries used for the event history query
     *
     * @type    IdoQuery[]
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
            'host_name'             => 'eh.host_name',
            'service_description'   => 'eh.service_description',
            'object_type'           => 'eh.object_type',
            'timestamp'             => 'eh.timestamp',
            'state'                 => 'eh.state',
            'output'                => 'eh.output',
            'type'                  => 'eh.type',
            'host_display_name'     => 'eh.host_display_name',
            'service_display_name'  => 'eh.service_display_name'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $columns = array(
            'timestamp',
            'output',
            'type',
            'state',
            'object_type',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        );
        $this->subQueries = array(
            $this->createSubQuery('Statehistory', $columns),
            $this->createSubQuery('Downtimestarthistory', $columns),
            $this->createSubQuery('Downtimeendhistory', $columns),
            $this->createSubQuery('Commenthistory', $columns),
            $this->createSubQuery('Commentdeletionhistory', $columns),
            $this->createSubQuery('Notificationhistory', $columns),
            $this->createSubQuery('Flappingstarthistory', $columns),
            $this->createSubQuery('Flappingendhistory', $columns)
        );
        $sub = $this->db->select()->union($this->subQueries, Zend_Db_Select::SQL_UNION_ALL);
        $this->select->from(array('eh' => $sub), array());
        $this->joinedVirtualTables['eventhistory'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function allowsCustomVars()
    {
        foreach ($this->subQueries as $query) {
            if (! $query->allowsCustomVars()) {
                return false;
            }
        }

        return true;
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
    public function where($condition, $value = null)
    {
        $this->requireColumn($condition);
        foreach ($this->subQueries as $sub) {
            $sub->where($condition, $value);
        }
        return $this;
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
}
