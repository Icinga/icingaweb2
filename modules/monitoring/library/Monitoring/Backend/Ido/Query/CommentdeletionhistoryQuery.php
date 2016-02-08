<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service comment removal records
 */
class CommentdeletionhistoryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'commenthistory' => array(
            'object_type' => 'cdh.object_type'
        ),
        'history' => array(
            'type'      => 'cdh.type',
            'timestamp' => 'cdh.timestamp',
            'object_id' => 'cdh.object_id',
            'state'     => 'cdh.state',
            'output'    => 'cdh.output'
        ),
        'hosts' => array(
            'host_display_name' => 'cdh.host_display_name',
            'host_name'         => 'cdh.host_name'
        ),
        'services' => array(
            'service_description'   => 'cdh.service_description',
            'service_display_name'  => 'cdh.service_display_name',
            'service_host_name'     => 'cdh.service_host_name'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $commentDeletionHistoryQuery;

    /**
     * Subqueries used for the comment history query
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
        $this->commentDeletionHistoryQuery = $this->db->select();
        $this->select->from(
            array('cdh' => $this->commentDeletionHistoryQuery),
            array()
        );
        $this->joinedVirtualTables['commenthistory'] = true;
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
            $this->columnMap['commenthistory'] + $this->columnMap['hosts']
        );
        foreach ($this->columnMap['services'] as $column => $_) {
            $columns[$column] = new Zend_Db_Expr('NULL');
        }
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $hosts = $this->createSubQuery('Hostcommentdeletionhistory', $columns);
        $this->subQueries[] = $hosts;
        $this->commentDeletionHistoryQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys(
            $this->columnMap['commenthistory'] + $this->columnMap['hosts'] + $this->columnMap['services']
        );
        if ($this->fetchHistoryColumns) {
            $columns = array_merge($columns, array_keys($this->columnMap['history']));
        }
        $services = $this->createSubQuery('Servicecommentdeletionhistory', $columns);
        $this->subQueries[] = $services;
        $this->commentDeletionHistoryQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
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
