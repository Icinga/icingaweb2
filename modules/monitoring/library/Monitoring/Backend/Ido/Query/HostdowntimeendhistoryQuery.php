<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host downtime end history records
 */
class HostdowntimeendhistoryQuery extends HostdowntimestarthistoryQuery
{
    /**
     * {@inheritdoc}
     */
    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(hdh.actual_end_time)') {
            return 'hdh.actual_end_time ' . $sign . ' ' . $this->timestampForSql(
                $this->valueToTimestamp($expression)
            );
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        parent::joinBaseTables(true);
        $this->select->where("hdh.actual_end_time > '1970-01-02 00:00:00'");
        $this->columnMap['downtimehistory']['type'] = "('dt_end')";
        $this->columnMap['downtimehistory']['timestamp'] = str_replace(
            'actual_start_time',
            'actual_end_time',
            $this->columnMap['downtimehistory']['timestamp']
        );
    }
}
