<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Authentication\Manager;
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
            'downtime_scheduled_end'    => 'd.downtime_scheduled_end',
            'downtime_scheduled_start'  => 'd.downtime_scheduled_start',
            'downtime_start'            => 'd.downtime_start',
            'object_type'               => 'd.object_type'
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
    public function addFilter(Filter $filter)
    {
        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }
        return $this;
    }

    /**
     * Apply host restrictions to a query
     *
     * @param IdoQuery $query
     */
    protected function applyHostRestrictions(IdoQuery $query)
    {
        $hostRestrictions = Filter::matchAny();
        foreach (Manager::getInstance()->getRestrictions('monitoring/hosts/filter') as $restriction) {
            $hostRestrictions->addFilter(Filter::fromQueryString($restriction));
        }
        $query->addFilter($hostRestrictions);
    }

    /**
     * Apply host and service restrictions to a query
     *
     * @param IdoQuery $query
     */
    protected function applyServiceRestrictions(IdoQuery $query)
    {
        $hostAndServiceRestrictions = Filter::matchAll();
        $hostRestrictions = Filter::matchAny();
        $serviceRestrictions = Filter::matchAny();
        foreach (Manager::getInstance()->getRestrictions('monitoring/hosts/filter') as $restriction) {
            $hostRestrictions->addFilter(Filter::fromQueryString($restriction));
        }
        foreach (Manager::getInstance()->getRestrictions('monitoring/services/filter') as $restriction) {
            $serviceRestrictions->addFilter(Filter::fromQueryString($restriction));
        }
        $hostAndServiceRestrictions->addFilter($hostRestrictions);
        $hostAndServiceRestrictions->addFilter($serviceRestrictions);
        $query->addFilter($hostAndServiceRestrictions);
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
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
        $hosts = $this->createSubQuery('hoststatus', $columns);
        $this->applyHostRestrictions($hosts);
        $this->subQueries[] = $hosts;
        $this->downtimeQuery->union(array($hosts), Zend_Db_Select::SQL_UNION_ALL);
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $columns = array_keys($this->columnMap['downtimes'] + $this->columnMap['hosts'] + $this->columnMap['services']);
        $services = $this->createSubQuery('servicestatus', $columns);
        $this->applyServiceRestrictions($services);
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
