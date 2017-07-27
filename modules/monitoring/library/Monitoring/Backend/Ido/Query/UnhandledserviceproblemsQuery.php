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
            array('service_description')
        );
        $this->subSelect->where('service_handled', 0);
        $this->subSelect->where('service_state', 2);
        $this->select->from(
            array('problems' => $this->subSelect->setIsSubQuery(true)),
            array()
        );
        $this->joinedVirtualTables['problems'] = true;
    }
}
