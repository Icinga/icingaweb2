<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host comment removal records
 */
class HostcommentdeletionhistoryQuery extends HostcommenthistoryQuery
{
    public function isTimestamp($field)
    {
        if (! parent::isTimestamp($field)) {
            return $field === 'hch.deletion_time';
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        parent::joinBaseTables();
        $this->select->where("hch.deletion_time > '1970-01-02 00:00:00'");
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
