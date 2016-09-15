<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service downtimes
 */
class DowntimeQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'downtimes' => array(
            'downtime_author'           => 'd.downtime_author',
            'downtime_author_name'      => 'd.downtime_author_name',
            'downtime_comment'          => 'd.downtime_comment',
            'downtime_duration'         => 'd.downtime_duration',
            'downtime_end'              => 'd.downtime_end',
            'downtime_entry_time'       => 'd.downtime_entry_time',
            'downtime_internal_id'      => 'd.downtime_internal_id',
            'downtime_is_fixed'         => 'd.downtime_is_fixed',
            'downtime_is_flexible'      => 'd.downtime_is_flexible',
            'downtime_is_in_effect'     => 'd.downtime_is_in_effect',
            'downtime_name'             => 'd.downtime_name',
            'downtime_scheduled_end'    => 'd.downtime_scheduled_end',
            'downtime_scheduled_start'  => 'd.downtime_scheduled_start',
            'downtime_start'            => 'd.downtime_start',
            'object_type'               => 'd.object_type',
            'instance_name'             => 'd.instance_name'
        ),
        'hosts' => array(
            'host_display_name'         => 'd.host_display_name',
            'host_name'                 => 'd.host_name',
            'host_state'                => 'd.host_state'
        ),
        'services' => array(
            'service_description'       => 'd.service_description',
            'service_display_name'      => 'd.service_display_name',
            'service_host_name'         => 'd.service_host_name',
            'service_state'             => 'd.service_state'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $downtimeQuery;

    /**
     * Subqueries used for the downtime query
     *
     * @var IdoQuery[]
     */
    protected $subQueries = array();

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
    protected function joinBaseTables()
    {
        if (version_compare($this->getIdoVersion(), '1.14.0', '<')) {
            $this->columnMap['downtimes']['downtime_name'] = '(NULL)';
        }
        $this->downtimeQuery = $this->db->select();
        $this->select->from(
            array('d' => $this->downtimeQuery),
            array()
        );
        $this->joinedVirtualTables['downtimes'] = true;
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $columns = array_keys($this->columnMap['downtimes'] + $this->columnMap['hosts']);
        foreach (array_keys($this->columnMap['services']) as $column) {
            $columns[$column] = new Zend_Db_Expr('NULL');
        }
        $hosts = $this->createSubQuery('hostdowntime', $columns);
        $this->subQueries[] = $hosts;
        $this->downtimeQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys($this->columnMap['downtimes'] + $this->columnMap['hosts'] + $this->columnMap['services']);
        $services = $this->createSubQuery('servicedowntime', $columns);
        $this->subQueries[] = $services;
        $this->downtimeQuery->union(array($services), Zend_Db_Select::SQL_UNION_ALL);
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
}
