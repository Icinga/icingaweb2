<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

/**
 * Helper for configuring the PHP environment
 */
class Environment
{
    /**
     * Raise the PHP memory_limit
     *
     * Unless it is not already set to a higher limit
     *
     * @param string|int $minimum
     */
    public static function raiseMemoryLimit($minimum = '512M')
    {
        if (is_string($minimum)) {
            $minimum = Format::unpackShorthandBytes($minimum);
        }

        if (Format::unpackShorthandBytes(ini_get('memory_limit')) < $minimum) {
            ini_set('memory_limit', $minimum);
        }
    }

    /**
     * Raise the PHP max_execution_time
     *
     * Unless it is not already configured to a higher value.
     *
     * @param int $minimum
     */
    public static function raiseExecutionTime($minimum = 300)
    {
        if ((int) ini_get('max_execution_time') < $minimum) {
            ini_set('max_execution_time', $minimum);
        }
    }
}
