<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service flapping start history records
 */
class FlappingstarthistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'flappinghistory' => array(
            'object_type' => 'fsh.object_type'
        ),
        'history' => array(
            'type'      => 'fsh.type',
            'timestamp' => 'fsh.timestamp',
            'object_id' => 'fsh.object_id',
            'state'     => 'fsh.state',
            'output'    => 'fsh.output'
        ),
        'hosts' => array(
            'host_display_name' => 'fsh.host_display_name',
            'host_name'         => 'fsh.host_name'
        ),
        'services' => array(
            'service_description'   => 'fsh.service_description',
            'service_display_name'  => 'fsh.service_display_name',
            'service_host_name'     => 'fsh.service_host_name'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $flappingStartHistoryQuery;

    /**
     * Subqueries used for the flapping start history query
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
        $this->flappingStartHistoryQuery = $this->db->select();
        $this->select->from(
            array('fsh' => $this->flappingStartHistoryQuery),
            array()
        );
        $this->joinedVirtualTables['flappinghistory'] = true;
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
            $this->columnMap['flappinghistory'] + $this->columnMap['hosts']
        );
        foreach ($this->columnMap['services'] as $column => $_) {
            $columns[$column] = new Zend_Db_Expr('NULL');
        }
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $hosts = $this->createSubQuery('Hostflappingstarthistory', $columns);
        $this->subQueries[] = $hosts;
        $this->flappingStartHistoryQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys(
            $this->columnMap['flappinghistory'] + $this->columnMap['hosts'] + $this->columnMap['services']
        );
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $services = $this->createSubQuery('Serviceflappingstarthistory', $columns);
        $this->subQueries[] = $services;
        $this->flappingStartHistoryQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
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
