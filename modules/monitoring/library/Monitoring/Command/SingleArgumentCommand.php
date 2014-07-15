<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Exception\ProgrammingError;
use Icinga\Protocol\Commandpipe\Command;

/**
 * Configurable simple command
 */
class SingleArgumentCommand extends Command
{
    /**
     * Value used for this command
     *
     * @var mixed
     */
    private $value;

    /**
     * Name of host command
     *
     * @var string
     */
    private $hostCommand;

    /**
     * Name of service command
     *
     * @var string
     */
    private $serviceCommand;

    /**
     * Name of global command
     *
     * @var array
     */
    private $globalCommands = array();

    /**
     * Ignore host in command string
     *
     * @var bool
     */
    private $ignoreObject = false;

    /**
     * Setter for this value
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Setter for command names
     *
     * @param   string  $hostCommand
     * @param   string  $serviceCommand
     */
    public function setCommand($hostCommand, $serviceCommand)
    {
        $this->hostCommand = $hostCommand;
        $this->serviceCommand = $serviceCommand;
    }

    /**
     * Set a bunch of global commands
     *
     * @param array $commands One or more commands to control global parameters
     */
    public function setGlobalCommands(array $commands)
    {
        $this->globalCommands = $commands;
        $this->globalCommand = true;
    }

    /**
     * Ignore object values upon command creation
     *
     * @param bool $flag
     */
    public function setObjectIgnoreFlag($flag = true)
    {
        $this->ignoreObject = (bool) $flag;
    }

    /**
     * Return this command's arguments in the order expected by the actual command definition
     *
     * @return array
     */
    public function getArguments()
    {
        if ($this->value !== null) {
            return array($this->value);
        } else {
            return array();
        }
    }

    /**
     * Build the argument string based on objects and arguments
     *
     * @param   array $objectNames
     *
     * @return  string String to append to command
     */
    private function getArgumentString(array $objectNames)
    {
        $data = array();
        if ($this->ignoreObject === true) {
            $data = $this->getArguments();
        } else {
            $data = array_merge($objectNames, $this->getArguments());
        }

        return implode(';', $data);
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string $hostname   The name of the host to insert
     *
     * @return  string              The string representation of the command
     */
    public function getHostCommand($hostname)
    {
        return strtoupper($this->hostCommand). ';' . $this->getArgumentString(array($hostname));
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string $hostname       The name of the host to insert
     * @param   string $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return strtoupper($this->serviceCommand)
        . ';'
        . $this->getArgumentString(array($hostname, $servicename));
    }

    /**
     * Getter for global command if configured
     *
     * @param string $instance
     *
     * @throws ProgrammingError
     * @return string
     */
    public function getGlobalCommand($instance = null)
    {
        if (!count($this->globalCommands)) {
            // This throws exception for us that globalCommand
            // is not implemented properly
            parent::getGlobalCommand();
        }

        if ($this->value === 'host') {
            return strtoupper($this->globalCommands[0]);
        }

        if ($this->value === 'service') {
            if (count($this->globalCommands) < 2) {
                throw new ProgrammingError('If use global values you need at least 2 global commands');
            }

            return strtoupper($this->globalCommands[1]);
        }

        return strtoupper(implode(';', $this->globalCommands));
    }
}
