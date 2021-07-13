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
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'hostgroupsummary' => array(
            'hostgroup_alias'                               => 'hostgroup_alias',
            'hostgroup_name'                                => 'hostgroup_name',
            'hosts_down'                                    => 'SUM(host_state = 1)',
            'hosts_down_handled'                            => 'SUM(host_state = 1 AND host_handled = 1)',
            'hosts_down_unhandled'                          => 'SUM(host_state = 1 AND host_handled = 0)',
            'hosts_pending'                                 => 'SUM(host_state = 99)',
            'hosts_severity'                                => 'MAX(host_severity)',
            'hosts_total'                                   => 'SUM(host_state IS NOT NULL)',
            'hosts_unreachable'                             => 'SUM(host_state = 2)',
            'hosts_unreachable_handled'                     => 'SUM(host_state = 2 AND host_handled = 1)',
            'hosts_unreachable_unhandled'                   => 'SUM(host_state = 2 AND host_handled = 0)',
            'hosts_up'                                      => 'SUM(host_state = 0)',
            'services_critical'                             => 'SUM(service_state = 2)',
            'services_critical_handled'                     => 'SUM(service_state = 2 AND service_handled = 1)',
            'services_critical_unhandled'                   => 'SUM(service_state = 2 AND service_handled = 0)',
            'services_ok'                                   => 'SUM(service_state = 0)',
            'services_pending'                              => 'SUM(service_state = 99)',
            'services_total'                                => 'SUM(service_state IS NOT NULL)',
            'services_unknown'                              => 'SUM(service_state = 3)',
            'services_unknown_handled'                      => 'SUM(service_state = 3 AND service_handled = 1)',
            'services_unknown_unhandled'                    => 'SUM(service_state = 3 AND service_handled = 0)',
            'services_warning'                              => 'SUM(service_state = 1)',
            'services_warning_handled'                      => 'SUM(service_state = 1 AND service_handled = 1)',
            'services_warning_unhandled'                    => 'SUM(service_state = 1 AND service_handled = 0)',
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
     * Count query
     *
     * @var IdoQuery
     */
    protected $countQuery;

    public function addFilter(Filter $filter)
    {
        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }
        $this->countQuery->applyFilter(clone $filter);
        return $this;
    }

    protected function joinBaseTables()
    {
        $this->countQuery = $this->createSubQuery(
            'Hostgroup',
            array()
        );
        $hosts = $this->createSubQuery(
            'Hostgroup',
            array(
                'hostgroup_alias',
                'hostgroup_name',
                'host_handled',
                'host_severity',
                'host_state',
                'service_handled'   => new Zend_Db_Expr('NULL'),
                'service_severity'  => new Zend_Db_Expr('0'),
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
                'host_severity' => new Zend_Db_Expr('0'),
                'host_state'    => new Zend_Db_Expr('NULL'),
                'service_handled',
                'service_severity',
                'service_state'
            )
        );
        $this->subQueries[] = $services;
        $emptyGroups = $this->createSubQuery(
            'Emptyhostgroup',
            [
                'hostgroup_alias',
                'hostgroup_name',
                'host_handled'      => new Zend_Db_Expr('NULL'),
                'host_severity'     => new Zend_Db_Expr('0'),
                'host_state'        => new Zend_Db_Expr('NULL'),
                'service_handled'   => new Zend_Db_Expr('NULL'),
                'service_severity'  => new Zend_Db_Expr('0'),
                'service_state'     => new Zend_Db_Expr('NULL'),
            ]
        );
        $this->subQueries[] = $emptyGroups;
        $this->summaryQuery = $this->db->select()->union(
            [$hosts, $services, $emptyGroups],
            Zend_Db_Select::SQL_UNION_ALL
        );
        $this->select->from(array('hostgroupsummary' => $this->summaryQuery), array());
        $this->group(array('hostgroup_name', 'hostgroup_alias'));
        $this->joinedVirtualTables['hostgroupsummary'] = true;
    }

    public function getCountQuery()
    {
        $count = $this->countQuery->select();
        $this->countQuery->applyFilterSql($count);
        $count->columns(array('hgo.object_id'));
        $count->group(array('hgo.object_id'));
        return $this->db->select()->from($count, array('cnt' => 'COUNT(*)'));
    }
}
