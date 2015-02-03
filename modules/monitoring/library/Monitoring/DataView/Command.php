<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Monitoring\DataView;

/**
 * View representation for commands
 */
class Command extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
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
