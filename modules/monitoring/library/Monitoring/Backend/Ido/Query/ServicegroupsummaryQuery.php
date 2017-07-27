<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;

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
            'services_critical'                             => 'SUM(CASE WHEN service_state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                     => 'SUM(CASE WHEN service_state = 2 AND service_handled = 1 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'                   => 'SUM(CASE WHEN service_state = 2 AND service_handled = 0 THEN 1 ELSE 0 END)',
            'services_ok'                                   => 'SUM(CASE WHEN service_state = 0 THEN 1 ELSE 0 END)',
            'services_pending'                              => 'SUM(CASE WHEN service_state = 99 THEN 1 ELSE 0 END)',
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
     * Subquery used for the summary query
     *
     * @var IdoQuery
     */
    protected $subQuery;

    /**
     * Count query
     *
     * @var IdoQuery
     */
    protected $countQuery;

    public function addFilter(Filter $filter)
    {
        $this->subQuery->applyFilter(clone $filter);
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
                'service_state'
            )
        );
        $subQuery->setIsSubQuery();
        $this->subQuery = $subQuery;
        $this->select->from(array('servicesgroupsummary' => $this->subQuery), array());
        $this->group(array('servicegroup_name', 'servicegroup_alias'));
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
