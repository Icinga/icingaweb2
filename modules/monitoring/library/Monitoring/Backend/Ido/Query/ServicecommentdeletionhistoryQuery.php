<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;

/**
 * Query for service comment removal records
 */
class ServicecommentdeletionhistoryQuery extends ServicecommenthistoryQuery
{
    protected function requireFilterColumns(Filter $filter)
    {
        if ($filter instanceof FilterExpression && $filter->getColumn() === 'timestamp') {
            $this->requireColumn('timestamp');
            $filter->setColumn('sch.deletion_time');
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
