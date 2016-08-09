<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host group summary
 */
class HostgroupsummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hostgroupsummary' => array(
            'hostgroup_alias'                               => 'hostgroup_alias',
            'hostgroup_name'                                => 'hostgroup_name',
            'hosts_down'                                    => 'SUM(CASE WHEN host_state = 1 THEN 1 ELSE 0 END)',
            'hosts_down_handled'                            => 'SUM(CASE WHEN host_state = 1 AND host_handled = 1 THEN 1 ELSE 0 END)',
            'hosts_down_unhandled'                          => 'SUM(CASE WHEN host_state = 1 AND host_handled = 0 THEN 1 ELSE 0 END)',
            'hosts_pending'                                 => 'SUM(CASE WHEN host_state = 99 THEN 1 ELSE 0 END)',
            'hosts_total'                                   => 'SUM(CASE WHEN host_state IS NOT NULL THEN 1 ELSE 0 END)',
            'hosts_unreachable'                             => 'SUM(CASE WHEN host_state = 2 THEN 1 ELSE 0 END)',
            'hosts_unreachable_handled'                     => 'SUM(CASE WHEN host_state = 2 AND host_handled = 1 THEN 1 ELSE 0 END)',
            'hosts_unreachable_unhandled'                   => 'SUM(CASE WHEN host_state = 2 AND host_handled = 0 THEN 1 ELSE 0 END)',
            'hosts_up'                                      => 'SUM(CASE WHEN host_state = 0 THEN 1 ELSE 0 END)',
            'services_critical'                             => 'SUM(CASE WHEN service_state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                     => 'SUM(CASE WHEN service_state = 2 AND service_handled = 1 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'                   => 'SUM(CASE WHEN service_state = 2 AND service_handled = 0 THEN 1 ELSE 0 END)',
            'services_ok'                                   => 'SUM(CASE WHEN service_state = 0 THEN 1 ELSE 0 END)',
            'services_pending'                              => 'SUM(CASE WHEN service_state = 99 THEN 1 ELSE 0 END)',
            'services_total'                                => 'SUM(CASE WHEN service_state IS NOT NULL THEN 1 ELSE 0 END)',
            'services_unknown'                              => 'SUM(CASE WHEN service_state = 3 THEN 1 ELSE 0 END)',
            'services_unknown_handled'                      => 'SUM(CASE WHEN service_state = 3 AND service_handled = 1 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled'                    => 'SUM(CASE WHEN service_state = 3 AND service_handled = 0 THEN 1 ELSE 0 END)',
            'services_warning'                              => 'SUM(CASE WHEN service_state = 1 THEN 1 ELSE 0 END)',
            'services_warning_handled'                      => 'SUM(CASE WHEN service_state = 1 AND service_handled = 1 THEN 1 ELSE 0 END)',
            'services_warning_unhandled'                    => 'SUM(CASE WHEN service_state = 1 AND service_handled = 0 THEN 1 ELSE 0 END)',
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $summaryQuery;

    /**
     * Subqueries used for the summary query
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
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        // TODO(el): Allow to switch between hard and soft states
        $hosts = $this->createSubQuery(
            'Hostgroup',
            array(
                'hostgroup_alias',
                'hostgroup_name',
                'host_handled',
                'host_state',
                'service_handled'   => new Zend_Db_Expr('NULL'),
                'service_state'     => new Zend_Db_Expr('NULL'),
            )
        );
        $this->subQueries[] = $hosts;
        $services = $this->createSubQuery(
            'Hostgroup',
            array(
                'hostgroup_alias',
                'hostgroup_name',
                'host_handled'  => new Zend_Db_Expr('NULL'),
                'host_state'    => new Zend_Db_Expr('NULL'),
                'service_handled',
                'service_state'
            )
        );
        $this->subQueries[] = $services;
        $this->summaryQuery = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->select->from(array('hostgroupsummary' => $this->summaryQuery), array());
        $this->group(array('hostgroup_name'));
        $this->joinedVirtualTables['hostgroupsummary'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function order($columnOrAlias, $dir = null)
    {
        if (! $this->hasAliasName($columnOrAlias)) {
            foreach ($this->subQueries as $sub) {
                $sub->requireColumn($columnOrAlias);
            }
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
