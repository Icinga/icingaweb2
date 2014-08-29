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
     * Get the command string
     *
     * @return string
     */
    abstract public function getCommandString();

    /**
     * Escape a command string
     *
     * @param   string $commandString
     *
     * @return  string
     */
    public function escape($commandString)
    {
        return str_replace(array("\r", "\n"), array('\r', '\n'), $commandString);
    }

    /**
     * Get the command as string with the current timestamp as the command submission time
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '[%u] %s',
            time(),
            $this->escape($this->getCommandString())
        );
    }
}
