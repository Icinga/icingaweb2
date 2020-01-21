<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class EventgridservicesQuery extends EventgridQuery
{
    /**
     * Join history related columns and tables, services only
     */
    protected function joinHistory()
    {
        $this->fetchHistoryColumns = true;
        $this->requireVirtualTable('services');
    }
}
