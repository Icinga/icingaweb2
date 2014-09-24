<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

/**
 * Base class for commands sent to an Icinga instance
 */
abstract class IcingaCommand
{
    /**
     * Get the name of the command
     *
     * @return string
     */
    public function getName()
    {
        return substr_replace(end(explode('\\', get_called_class())), '', -7);  // Remove 'Command' Suffix
    }
}
