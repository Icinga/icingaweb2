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
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hoststatussummary' => array(
            'hostgroup'                                     => 'hostgroup COLLATE latin1_general_ci',
            'hostgroup_alias'                               => 'hostgroup_alias COLLATE latin1_general_ci',
            'hostgroup_name'                                => 'hostgroup_name',
            'hosts_down'                                    => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 THEN 1 ELSE 0 END)',
            'hosts_down_handled'                            => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND handled != 0 THEN 1 ELSE 0 END)',
            'hosts_down_handled_last_state_change'          => 'MAX(CASE WHEN object_type = \'host\' AND state = 1 AND handled != 0 THEN state_change ELSE 0 END)',
            'hosts_down_unhandled'                          => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND handled = 0 THEN 1 ELSE 0 END)',
            'hosts_down_unhandled_last_state_change'        => 'MAX(CASE WHEN object_type = \'host\' AND state = 1 AND handled = 0 THEN state_change ELSE 0 END)',
            'hosts_pending'                                 => 'SUM(CASE WHEN object_type = \'host\' AND state = 99 THEN 1 ELSE 0 END)',
            'hosts_pending_last_state_change'               => 'MAX(CASE WHEN object_type = \'host\' AND state = 99 THEN state_change ELSE 0 END)',
            'hosts_severity'                                => 'MAX(CASE WHEN object_type = \'host\' THEN severity ELSE 0 END)',
            'hosts_total'                                   => 'SUM(CASE WHEN object_type = \'host\' THEN 1 ELSE 0 END)',
            'hosts_unreachable'                             => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 THEN 1 ELSE 0 END)',
            'hosts_unreachable_handled'                     => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND handled != 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_handled_last_state_change'   => 'MAX(CASE WHEN object_type = \'host\' AND state = 2 AND handled != 0 THEN state_change ELSE 0 END)',
            'hosts_unreachable_unhandled'                   => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND handled = 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_unhandled_last_state_change' => 'MAX(CASE WHEN object_type = \'host\' AND state = 2 AND handled = 0 THEN state_change ELSE 0 END)',
            'hosts_up'                                      => 'SUM(CASE WHEN object_type = \'host\' AND state = 0 THEN 1 ELSE 0 END)',
            'hosts_up_last_state_change'                    => 'MAX(CASE WHEN object_type = \'host\' AND state = 0 THEN state_change ELSE 0 END)',
            'services_critical'                             => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                     => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_state > 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_state = 0 THEN 1 ELSE 0 END)',
            'services_ok'                                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 THEN 1 ELSE 0 END)',
            'services_pending'                              => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 THEN 1 ELSE 0 END)',
            'services_severity'                             => 'MAX(CASE WHEN object_type = \'service\' THEN severity ELSE 0 END)',
            'services_total'                                => 'SUM(CASE WHEN object_type = \'service\' THEN 1 ELSE 0 END)',
            'services_unknown'                              => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 THEN 1 ELSE 0 END)',
            'services_unknown_handled'                      => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_state > 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled'                    => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_state = 0 THEN 1 ELSE 0 END)',
            'services_warning'                              => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 THEN 1 ELSE 0 END)',
            'services_warning_handled'                      => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_state > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled'                    => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_state = 0 THEN 1 ELSE 0 END)',
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
        // TODO(el): Allow to switch between hard and soft states
        $hosts = $this->createSubQuery(
            'Hoststatus',
            array(
                'handled'       => 'host_handled',
                'host_state'    => new Zend_Db_Expr('NULL'),
                'hostgroup_alias',
                'hostgroup_name',
                'object_type',
                'severity'      => 'host_severity',
                'state'         => 'host_state',
                'state_change'  => 'host_last_state_change'
            )
        );
        $hosts->select()->where('hgo.name1 IS NOT NULL'); // TODO(9458): Should be possible using our filters!
        $this->subQueries[] = $hosts;
        $services = $this->createSubQuery(
            'Servicestatus',
            array(
                'handled'       => 'service_handled',
                'host_state'    => 'host_hard_state',
                'hostgroup_alias',
                'hostgroup_name',
                'object_type',
                'severity'      => new Zend_Db_Expr('NULL'),
                'state'         => 'service_state',
                'state_change'  => 'service_last_state_change'
            )
        );
        $services->select()->where('hgo.name1 IS NOT NULL'); // TODO(9458): Should be possible using our filters!
        $this->subQueries[] = $services;
        $this->summaryQuery = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->select->from(array('statussummary' => $this->summaryQuery), array());
        $this->group(array('statussummary.hostgroup_name', 'statussummary.hostgroup_alias'));
        $this->joinedVirtualTables['hoststatussummary'] = true;
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
