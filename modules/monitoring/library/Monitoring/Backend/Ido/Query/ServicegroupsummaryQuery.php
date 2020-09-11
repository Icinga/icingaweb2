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
            'servicegroup_alias'                            => 'sg.alias',
            'servicegroup_name'                             => 'sgo.name1',
            'services_critical'                             => 'SUM(CASE WHEN service_state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                     => 'SUM(CASE WHEN service_state = 2 AND service_handled = 1 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'                   => 'SUM(CASE WHEN service_state = 2 AND service_handled = 0 THEN 1 ELSE 0 END)',
            'services_ok'                                   => 'SUM(CASE WHEN service_state = 0 THEN 1 ELSE 0 END)',
            'services_pending'                              => 'SUM(CASE WHEN service_state = 99 THEN 1 ELSE 0 END)',
            'services_severity'                             => 'MAX(service_severity)',
            'services_total'                                => 'SUM(1)',
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
    protected $subQueries = [];

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
        $services = $this->createSubQuery('servicestatus');
        $services->columns([
            new Zend_Db_Expr('s.service_object_id'),
            'service_handled',
            'service_severity',
            'service_state'
        ]);

        $this->subQueries = [$services];

        $states = $this->db->select()->union($this->subQueries, \Zend_Db_Select::SQL_UNION_ALL);

        $this
            ->select
            ->from(['sg' => 'icinga_servicegroups'], null)
            ->join(['sgo' => 'icinga_objects'], 'sgo.object_id = sg.servicegroup_object_id AND sgo.objecttype_id = 4 AND sgo.is_active = 1', null)
            ->joinLeft(['sgm' => 'icinga_servicegroup_members'], 'sgm.servicegroup_id = sg.servicegroup_id', null)
            ->joinLeft(['so' => 'icinga_objects'], 'so.object_id = sgm.service_object_id AND so.objecttype_id = 2 AND so.is_active = 1', null)
            ->joinLeft(['s' => 'icinga_services'], 's.service_object_id = so.object_id', null)
            ->joinLeft(['states' => $states], 'states.service_object_id = so.object_id', null);

        $this->group(['servicegroup_name']);

        $this->joinedVirtualTables['servicegroupsummary'] = true;

        $this->countQuery = $this->createSubQuery('Servicegroup', []);
    }

    public function getSelectQuery()
    {
        if ($this->isFiltered) {
            $this->select->having('services_total > 0');
        }

        return parent::getSelectQuery();
    }

    public function getCountQuery()
    {
        $count = $this->countQuery->select();
        $this->countQuery->applyFilterSql($count);
        $count->columns(array('sgo.object_id'));
        if ($this->isFiltered) {
            $count->group(array('sgo.object_id'));
        }
        return $this->db->select()->from($count, array('cnt' => 'COUNT(*)'));
    }
}
