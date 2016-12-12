<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service flapping end history records
 */
class ServiceflappingendhistoryQuery extends ServiceflappingstarthistoryQuery
{
    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('sfh' => $this->prefix . 'flappinghistory'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sfh.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );

        $this->select->where('sfh.event_type = 1001');

        $this->joinedVirtualTables['flappinghistory'] = true;

        $this->columnMap['flappinghistory']['type'] = '(\'flapping_deleted\')';
    }
}
