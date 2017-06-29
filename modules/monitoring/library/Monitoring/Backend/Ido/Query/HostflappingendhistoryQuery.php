<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host flapping end history records
 */
class HostflappingendhistoryQuery extends HostflappingstarthistoryQuery
{
    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('hfh' => $this->prefix . 'flappinghistory'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = hfh.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );

        $this->select->where('hfh.event_type = 1001');

        $this->joinedVirtualTables['flappinghistory'] = true;

        $this->columnMap['flappinghistory']['type'] = '(\'flapping_deleted\')';
    }
}
