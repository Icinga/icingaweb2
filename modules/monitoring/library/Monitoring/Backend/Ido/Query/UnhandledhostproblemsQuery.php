<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;

/**
 * Query for unhandled host problems
 */
class UnhandledhostproblemsQuery extends IdoQuery
{
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'problems' => array(
            'hosts_down_unhandled' => 'COUNT(*)',
        )
    );

    /**
     * The service status sub select
     *
     * @var HoststatusQuery
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
            'Hoststatus',
            array('host_name')
        );
        $this->subSelect->where('host_handled', 0);
        $this->subSelect->where('host_state', 1);
        $this->select->from(
            array('problems' => $this->subSelect->setIsSubQuery(true)),
            array()
        );
        $this->joinedVirtualTables['problems'] = true;
    }
}
