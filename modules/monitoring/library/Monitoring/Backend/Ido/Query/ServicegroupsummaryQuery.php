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

    protected $allowCustomVars = true;

    protected $columnMap = array(
        'servicegroupsummary' => array(
            'servicegroup_alias'                            => 'servicegroup_alias',
            'servicegroup_name'                             => 'servicegroup_name',
            'services_critical'                             => 'SUM(service_state = 2)',
            'services_critical_handled'                     => 'SUM(service_state = 2 AND service_handled = 1)',
            'services_critical_unhandled'                   => 'SUM(service_state = 2 AND service_handled = 0)',
            'services_ok'                                   => 'SUM(service_state = 0)',
            'services_pending'                              => 'SUM(service_state = 99)',
            'services_severity'                             => 'MAX(service_severity)',
            'services_total'                                => 'SUM(1)',
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
    protected $subQueries = [];

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
            'Servicegroup',
            array()
        );
        $subQuery = $this->createSubQuery(
            'Servicegroup',
            array(
                'servicegroup_alias',
                'servicegroup_name',
                'service_handled',
                'service_severity',
                'service_state'
            )
        );
        $this->subQueries[] = $subQuery;
        $emptyGroups = $this->createSubQuery(
            'Emptyservicegroup',
            [
                'servicegroup_alias',
                'servicegroup_name',
                'service_handled'   => new Zend_Db_Expr('NULL'),
                'service_severity'  => new Zend_Db_Expr('0'),
                'service_state'     => new Zend_Db_Expr('NULL'),
            ]
        );
        $this->subQueries[] = $emptyGroups;
        $this->summaryQuery = $this->db->select()->union(
            [$subQuery, $emptyGroups],
            Zend_Db_Select::SQL_UNION_ALL
        );
        $this->select->from(['servicesgroupsummary' => $this->summaryQuery], []);
        $this->group(['servicegroup_name', 'servicegroup_alias']);
        $this->joinedVirtualTables['servicegroupsummary'] = true;
    }

    public function getCountQuery()
    {
        $count = $this->countQuery->select();
        $this->countQuery->applyFilterSql($count);
        $count->columns(array('sgo.object_id'));
        $count->group(array('sgo.object_id'));
        return $this->db->select()->from($count, array('cnt' => 'COUNT(*)'));
    }
}
