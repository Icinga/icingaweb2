<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * View representation for commands
 */
class Command extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'command_id',
            'command_instance_id',
            'command_config_type',
            'command_line',
            'command_name'
        );
    }

}
