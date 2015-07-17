<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service downtime start history records
 */
class DowntimestarthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'downtimehistory' => array(
            'object_type' => 'dsh.object_type'
        ),
        'history' => array(
            'type'      => 'dsh.type',
            'timestamp' => 'dsh.timestamp',
            'object_id' => 'dsh.object_id',
            'state'     => 'dsh.state',
            'output'    => 'dsh.output'
        ),
        'hosts' => array(
            'host_display_name' => 'dsh.host_display_name',
            'host_name'         => 'dsh.host_name'
        ),
        'services' => array(
            'service_description'   => 'dsh.service_description',
            'service_display_name'  => 'dsh.service_display_name',
            'service_host_name'     => 'dsh.service_host_name'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $downtimeStartHistoryQuery;

    /**
     * Subqueries used for the downtime start history query
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
    protected function joinBaseTables()
    {
        $this->downtimeStartHistoryQuery = $this->db->select();
        $this->select->from(
            array('dsh' => $this->downtimeStartHistoryQuery),
            array()
        );
        $this->joinedVirtualTables['downtimehistory'] = true;
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
            $this->columnMap['downtimehistory'] + $this->columnMap['hosts']
        );
        foreach ($this->columnMap['services'] as $column => $_) {
            $columns[$column] = new Zend_Db_Expr('NULL');
        }
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $hosts = $this->createSubQuery('Hostdowntimestarthistory', $columns);
        $this->subQueries[] = $hosts;
        $this->downtimeStartHistoryQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys(
            $this->columnMap['downtimehistory'] + $this->columnMap['hosts'] + $this->columnMap['services']
        );
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $services = $this->createSubQuery('Servicedowntimestarthistory', $columns);
        $this->subQueries[] = $services;
        $this->downtimeStartHistoryQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
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
