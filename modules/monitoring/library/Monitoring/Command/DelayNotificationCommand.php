<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Protocol\Commandpipe\Command;

/**
 * Command to delay a notification
 */
class DelayNotificationCommand extends Command
{
    /**
     * The delay in seconds
     *
     * @var int
     */
    private $delay;

    /**
     * Initialise a new delay notification command object
     *
     * @param   int     $delay      How long, in seconds, notifications should be delayed
     */
    public function __construct($delay)
    {
        $this->delay = $delay;
    }

    /**
     * Set how long notifications should be delayed
     *
     * @param   int     $seconds    In seconds
     *
     * @return  self
     */
    public function setDelay($seconds)
    {
        $this->delay = (int) $seconds;
        return $this;
    }

    /**
     * Return this command's parameters properly arranged in an array
     *
     * @return  array
     * @see     Command::getArguments()
     */
    public function getArguments()
    {
        return array($this->delay);
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string  $hostname   The name of the host to insert
     *
     * @return  string              The string representation of the command
     * @see     Command::getHostCommand()
     */
    public function getHostCommand($hostname)
    {
        return 'DELAY_HOST_NOTIFICATION;' . implode(';', array_merge(array($hostname), $this->getArguments()));
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string  $hostname       The name of the host to insert
     * @param   string  $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     * @see     Command::getServiceCommand()
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return 'DELAY_SVC_NOTIFICATION;' . implode(
            ';',
            array_merge(
                array($hostname, $servicename),
                $this->getArguments()
            )
        );
    }
}
