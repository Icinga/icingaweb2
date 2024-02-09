<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;

/**
 * Query for host downtime end history records
 */
class HostdowntimeendhistoryQuery extends HostdowntimestarthistoryQuery
{
    protected function requireFilterColumns(Filter $filter)
    {
        if ($filter instanceof FilterExpression && $filter->getColumn() === 'timestamp') {
            $this->requireColumn('timestamp');
            $filter->setColumn('hdh.actual_end_time');
            $filter->setExpression($this->timestampForSql($this->valueToTimestamp($filter->getExpression())));
            return null;
        }

        return parent::requireFilterColumns($filter);
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        parent::joinBaseTables(true);
        $this->select->where(
            "hdh.actual_end_time > '1970-01-02 00:00:00' AND hdh.was_started = 1"
        );
        $this->columnMap['downtimehistory']['type'] = "('dt_end')";
        $this->columnMap['downtimehistory']['timestamp'] = str_replace(
            'actual_start_time',
            'actual_end_time',
            $this->columnMap['downtimehistory']['timestamp']
        );
    }
}
