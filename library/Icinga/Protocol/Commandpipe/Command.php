<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

use Icinga\Exception\ProgrammingError;

/**
 * Base class for any concrete command implementation
 */
abstract class Command
{
    /**
     * Whether hosts are ignored in case of a host- or servicegroup
     *
     * @var bool
     */
    protected $withoutHosts = false;

    /**
     * Whether services are ignored in case of a host- or servicegroup
     *
     * @var bool
     */
    protected $withoutServices = false;

    /**
     * Whether child hosts are going to be included in case of a host command
     *
     * @var bool
     */
    protected $withChildren = false;

    /**
     * Whether only services are going to be included in case of a host command
     *
     * @var bool
     */
    protected $onlyServices = false;

    /**
     * Whether it is a global command or not
     *
     * @var bool
     */
    protected $globalCommand = false;

    /**
     * Set whether this command should only affect the services of a host- or servicegroup
     *
     * @param   bool    $state
     * @return  self
     */
    public function excludeHosts($state = true)
    {
        $this->withoutHosts = (bool) $state;
        return $this;
    }

    /**
     * Set whether this command should only affect the hosts of a host- or servicegroup
     *
     * @param   bool    $state
     * @return  self
     */
    public function excludeServices($state = true)
    {
        $this->withoutServices = (bool) $state;
        return $this;
    }

    /**
     * Set whether this command should also affect all children hosts of a host
     *
     * @param   bool    $state
     * @return  self
     */
    public function includeChildren($state = true)
    {
        $this->withChildren = (bool) $state;
        return $this;
    }

    /**
     * Set whether this command only affects the services associated with a particular host
     *
     * @param   bool    $state
     * @return  self
     */
    public function excludeHost($state = true)
    {
        $this->onlyServices = (bool) $state;
        return $this;
    }

    /**
     * Getter for flag whether a command is global
     * @return bool
     */
    public function provideGlobalCommand()
    {
        return (bool) $this->globalCommand;
    }

    /**
     * Return this command's arguments in the order expected by the actual command definition
     *
     * @return array
     */
    abstract public function getArguments();

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string  $hostname   The name of the host to insert
     *
     * @return  string              The string representation of the command
     */
    abstract public function getHostCommand($hostname);

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string  $hostname       The name of the host to insert
     * @param   string  $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     */
    abstract public function getServiceCommand($hostname, $servicename);

    /**
     * Return the command as a string with the given hostgroup being inserted
     *
     * @param   string  $hostgroup  The name of the hostgroup to insert
     *
     * @return  string              The string representation of the command
     */
    public function getHostgroupCommand($hostgroup)
    {
        throw new ProgrammingError(get_class($this) . ' does not provide a hostgroup command');
    }

    /**
     * Return the command as a string with the given servicegroup being inserted
     *
     * @param   string  $servicegroup   The name of the servicegroup to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getServicegroupCommand($servicegroup)
    {
        throw new ProgrammingError(get_class($this) . ' does not provide a servicegroup command');
    }

    /**
     * Return the command as a string for the whole instance
     *
     * @param   string $instance
     *
     * @return  string
     * @throws  ProgrammingError
     */
    public function getGlobalCommand($instance = null)
    {
        throw new ProgrammingError(getclass($this) . ' does not provide a global command');
    }
}
