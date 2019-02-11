<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;

/**
 * Query for unhandled service problems
 */
class UnhandledserviceproblemsQuery extends IdoQuery
{
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'problems' => array(
            'services_critical_unhandled' => 'COUNT(*)',
        )
    );

    /**
     * The service status sub select
     *
     * @var ServicestatusQuery
     */
    protected $subSelect;

    public function addFilter(Filter $filter)
    {
        $this->subSelect->applyFilter(clone $filter);
        return $this;
    }

    protected function joinBaseTables()
    {
        $this->subSelect = $this->createSubQuery(
            'Servicestatus',
            [new \Zend_Db_Expr(1)]
        );
        $this->subSelect->where('service_handled', 0);
        $this->subSelect->where('service_state', 2);
        // WIP: Force index idx_servicestatus_problems
//        if ($this->getMonitoringBackend()->useOptimizedQueries()) {
//            $zendSelect = $this->subSelect->select();
//
//            $partsProp = (new \ReflectionClass('\Zend_Db_Select'))->getProperty('_parts');
//            $partsProp->setAccessible(true);
//
//            $parts = $partsProp->getValue($zendSelect);
//
//            $parts['from']['ss USE INDEX(idx_servicestatus_problems)'] = $parts['from']['ss'];
//            unset($parts['from']['ss']);
//
//            $partsProp->setValue($zendSelect, $parts);
//        }
        $this->select->from(
            array('problems' => $this->subSelect->setIsSubQuery(true)),
            array()
        );
        $this->joinedVirtualTables['problems'] = true;
    }
}
