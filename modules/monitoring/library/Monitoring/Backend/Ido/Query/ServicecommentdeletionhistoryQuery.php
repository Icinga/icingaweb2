<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service comment removal records
 */
class ServicecommentdeletionhistoryQuery extends ServicecommenthistoryQuery
{
    /**
     * {@inheritdoc}
     */
    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(sch.deletion_time)') {
            return 'sch.deletion_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        parent::joinBaseTables();
        $this->select->where("sch.deletion_time > '1970-01-02 00:00:00'");
        $this->columnMap['commenthistory']['timestamp'] = str_replace(
            'comment_time',
            'deletion_time',
            $this->columnMap['commenthistory']['timestamp']
        );
        $this->columnMap['commenthistory']['type'] = str_replace(
            'END)',
            "END || '_deleted')",
            $this->columnMap['commenthistory']['type']
        );
    }
}
