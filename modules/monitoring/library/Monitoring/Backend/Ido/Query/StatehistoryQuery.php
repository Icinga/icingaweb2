<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service state history records
 */
class StatehistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'statehistory' => array(
            'object_type' => 'sth.object_type'
        ),
        'history' => array(
            'type'      => 'sth.type',
            'timestamp' => 'sth.timestamp',
            'object_id' => 'sth.object_id',
            'state'     => 'sth.state',
            'output'    => 'sth.output'
        ),
        'hosts' => array(
            'host_display_name' => 'sth.host_display_name',
            'host_name'         => 'sth.host_name'
        ),
        'services' => array(
            'service_description'   => 'sth.service_description',
            'service_display_name'  => 'sth.service_display_name',
            'service_host_name'     => 'sth.service_host_name'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $stateHistoryQuery;

    /**
     * Subqueries used for the state history query
     *
     * @var IdoQuery[]
     */
    protected $subQueries = array();

    /**
     * Whether to additionally select all history columns
     *
     * @var bool
     */
    protected $fetchHistoryColumns = false;

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
    protected function joinBaseTables()
    {
        $this->stateHistoryQuery = $this->db->select();
        $this->select->from(
            array('sth' => $this->stateHistoryQuery),
            array()
        );
        $this->joinedVirtualTables['statehistory'] = true;
    }

    /**
     * Join history related columns and tables
     */
    protected function joinHistory()
    {
        // TODO: Ensure that one is selecting the history columns first...
        $this->fetchHistoryColumns = true;
        $this->requireVirtualTable('hosts');
        $this->requireVirtualTable('services');
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $columns = array_keys(
            $this->columnMap['statehistory'] + $this->columnMap['hosts']
        );
        foreach ($this->columnMap['services'] as $column => $_) {
            $columns[$column] = new Zend_Db_Expr('NULL');
        }
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $hosts = $this->createSubQuery('Hoststatehistory', $columns);
        $this->subQueries[] = $hosts;
        $this->stateHistoryQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys(
            $this->columnMap['statehistory'] + $this->columnMap['hosts'] + $this->columnMap['services']
        );
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $services = $this->createSubQuery('Servicestatehistory', $columns);
        $this->subQueries[] = $services;
        $this->stateHistoryQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
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
