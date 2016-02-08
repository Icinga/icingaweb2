<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;
use Zend_Db_Expr;
use Zend_Db_Select;

/**
 * Query for service group summary
 */
class ServicegroupsummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'servicestatussummary' => array(
            'servicegroup'                                  => 'servicegroup COLLATE latin1_general_ci',
            'servicegroup_alias'                            => 'servicegroup_alias COLLATE latin1_general_ci',
            'servicegroup_name'                             => 'servicegroup_name',
            'services_critical'                             => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                     => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_state > 0 THEN 1 ELSE 0 END)',
            'services_critical_handled_last_state_change'   => 'MAX(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_state > 0 THEN state_change ELSE 0 END)',
            'services_critical_unhandled'                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_state = 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled_last_state_change' => 'MAX(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_state = 0 THEN state_change ELSE 0 END)',
            'services_ok'                                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 THEN 1 ELSE 0 END)',
            'services_ok_last_state_change'                 => 'MAX(CASE WHEN object_type = \'service\' AND state = 0 THEN state_change ELSE 0 END)',
            'services_pending'                              => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 THEN 1 ELSE 0 END)',
            'services_pending_last_state_change'            => 'MAX(CASE WHEN object_type = \'service\' AND state = 99 THEN state_change ELSE 0 END)',
            'services_severity'                             => 'MAX(CASE WHEN object_type = \'service\' THEN severity ELSE 0 END)',
            'services_total'                                => 'SUM(CASE WHEN object_type = \'service\' THEN 1 ELSE 0 END)',
            'services_unknown'                              => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 THEN 1 ELSE 0 END)',
            'services_unknown_handled'                      => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_state > 0 THEN 1 ELSE 0 END)',
            'services_unknown_handled_last_state_change'    => 'MAX(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_state > 0 THEN state_change ELSE 0 END)',
            'services_unknown_unhandled'                    => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_state = 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled_last_state_change'  => 'MAX(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_state = 0 THEN state_change ELSE 0 END)',
            'services_warning'                              => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 THEN 1 ELSE 0 END)',
            'services_warning_handled'                      => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_state > 0 THEN 1 ELSE 0 END)',
            'services_warning_handled_last_state_change'    => 'MAX(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_state > 0 THEN state_change ELSE 0 END)',
            'services_warning_unhandled'                    => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_state = 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled_last_state_change'  => 'MAX(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_state = 0 THEN state_change ELSE 0 END)'
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
                'servicegroup_alias',
                'servicegroup_name',
                'object_type',
                'severity'      => new Zend_Db_Expr('NULL'),
                'state'         => 'host_state',
                'state_change'  => 'host_last_state_change'
            )
        );
        $hosts->select()->where('sgo.name1 IS NOT NULL'); // TODO(9458): Should be possible using our filters!
        $this->subQueries[] = $hosts;
        $services = $this->createSubQuery(
            'Servicestatus',
            array(
                'handled'       => 'service_handled',
                'host_state'    => 'host_state',
                'servicegroup_alias',
                'servicegroup_name',
                'object_type',
                'severity'      => 'service_severity',
                'state'         => 'service_state',
                'state_change'  => 'service_last_state_change'
            )
        );
        $services->select()->where('sgo.name1 IS NOT NULL'); // TODO(9458): Should be possible using our filters!
        $this->subQueries[] = $services;
        $this->summaryQuery = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->select->from(array('statussummary' => $this->summaryQuery), array());
        $this->group(array('servicegroup_name', 'servicegroup_alias'));
        $this->joinedVirtualTables['servicestatussummary'] = true;
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
