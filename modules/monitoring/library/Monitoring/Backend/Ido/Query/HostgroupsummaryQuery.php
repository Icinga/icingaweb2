<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;
use Zend_Db_Expr;
use Zend_Db_Select;

/**
 * Query for host group summary
 */
class HostgroupsummaryQuery extends IdoQuery
{
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'hostgroupsummary' => array(
            'hostgroup_alias'                               => 'hg.alias',
            'hostgroup_name'                                => 'hgo.name1',
            'hosts_down'                                    => 'SUM(CASE WHEN host_state = 1 THEN 1 ELSE 0 END)',
            'hosts_down_handled'                            => 'SUM(CASE WHEN host_state = 1 AND host_handled = 1 THEN 1 ELSE 0 END)',
            'hosts_down_unhandled'                          => 'SUM(CASE WHEN host_state = 1 AND host_handled = 0 THEN 1 ELSE 0 END)',
            'hosts_pending'                                 => 'SUM(CASE WHEN host_state = 99 THEN 1 ELSE 0 END)',
            'hosts_severity'                                => 'MAX(host_severity)',
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
     * Count query
     *
     * @var IdoQuery
     */
    protected $countQuery;

    protected $isFiltered = false;

    public function addFilter(Filter $filter)
    {
        if (! $filter->isEmpty()) {
            $this->isFiltered = true;
        }

        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }
        $this->countQuery->applyFilter(clone $filter);
        return $this;
    }

    protected function joinBaseTables()
    {
        $hosts = $this->createSubQuery('hoststatus');
        $hosts->requireVirtualTable('hoststatus');
        $hosts->columns([
            new Zend_Db_Expr('hs.host_object_id'),
            'host_state',
            'host_handled',
            'host_severity',
            'service_handled'   => new Zend_Db_Expr('NULL'),
            'service_state'     => new Zend_Db_Expr('NULL'),
        ]);

        $services = $this->createSubQuery('servicestatus');
        $services->columns([
            new Zend_Db_Expr('s.host_object_id'),
            'host_handled'  => new Zend_Db_Expr('NULL'),
            'host_state'    => new Zend_Db_Expr('NULL'),
            'host_severity' => new Zend_Db_Expr('NULL'),
            'service_handled',
            'service_state'
        ]);

        $this->subQueries = [$hosts, $services];

        $states = $this->db->select()->union($this->subQueries, \Zend_Db_Select::SQL_UNION_ALL);

        $this
            ->select
            ->from(['hg' => 'icinga_hostgroups'], null)
            ->join(['hgo' => 'icinga_objects'], 'hgo.object_id = hg.hostgroup_object_id AND hgo.objecttype_id = 3 AND hgo.is_active = 1', null)
            ->joinLeft(['hgm' => 'icinga_hostgroup_members'], 'hgm.hostgroup_id = hg.hostgroup_id', null)
            ->joinLeft(['ho' => 'icinga_objects'], 'ho.object_id = hgm.host_object_id AND ho.objecttype_id = 1 AND ho.is_active = 1', null)
            ->joinLeft(['h' => 'icinga_hosts'], 'h.host_object_id = ho.object_id', null)
            ->joinLeft(['states' => $states], 'states.host_object_id = ho.object_id', null);

        $this->group(['hostgroup_name']);

        $this->joinedVirtualTables['hostgroupsummary'] = true;

        $this->countQuery = $this->createSubQuery('Hostgroup', []);
    }

    public function getSelectQuery()
    {
        if ($this->isFiltered) {
            $this->select->having('hosts_total > 0');
        }

        return parent::getSelectQuery();
    }

    public function getCountQuery()
    {
        $count = $this->countQuery->select();
        $this->countQuery->applyFilterSql($count);
        $count->columns(new Zend_Db_Expr(1));
        if ($this->isFiltered) {
            $count->group(array('hgo.object_id'));
        }
        return $this->db->select()->from($count, array('cnt' => 'COUNT(*)'));
    }
}
