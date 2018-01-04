<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class EventgridhostsQuery extends EventgridQuery
{

    /**
     * Join history related columns and tables, hosts only
     */
    protected function joinHistory()
    {
        $this->fetchHistoryColumns = true;
        $this->requireVirtualTable('hosts');
    }
}
