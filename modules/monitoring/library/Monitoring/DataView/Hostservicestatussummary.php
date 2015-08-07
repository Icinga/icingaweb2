<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Hostservicestatussummary extends Hoststatus
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array_merge(
            parent::getColumns(),
            array(
                'host_name',
                'unhandled_service_count'
            )
        );
    }
}
