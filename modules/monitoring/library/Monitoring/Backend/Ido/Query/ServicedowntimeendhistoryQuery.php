<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host downtime end history records
 */
class ServicedowntimeendhistoryQuery extends ServicedowntimestarthistoryQuery
{
    public function isTimestamp($field)
    {
        if (! parent::isTimestamp($field)) {
            return $field === 'sdh.actual_end_time';
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        parent::joinBaseTables(true);
        $this->select->where("sdh.actual_end_time > '1970-01-02 00:00:00'");
        $this->columnMap['downtimehistory']['type'] = "('dt_end')";
        $this->columnMap['downtimehistory']['timestamp'] = str_replace(
            'actual_start_time',
            'actual_end_time',
            $this->columnMap['downtimehistory']['timestamp']
        );
    }
}
