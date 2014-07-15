<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Exception\ProgrammingError;
use Icinga\Protocol\Commandpipe\Command;

/**
 * Disable notifications with expire
 */
class DisableNotificationWithExpireCommand extends Command
{
    /**
     * Timestamp when deactivation should expire
     *
     * @var integer
     */
    private $expirationTimestamp;

    /**
     * Create a new instance of this command
     */
    public function __construct()
    {
        // There is now object specific implementation, only global
        $this->globalCommand = true;
    }

    /**
     * Setter for expiration timestamp
     *
     * @param integer $timestamp
     */
    public function setExpirationTimestamp($timestamp)
    {
        $this->expirationTimestamp = $timestamp;
    }

    /**
     * Return this command's arguments in the order expected by the actual command definition
     *
     * @return array
     */
    public function getArguments()
    {
        return array($this->expirationTimestamp);
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string $hostname   The name of the host to insert
     * @throws  ProgrammingError
     *
     * @return  string              The string representation of the command
     */
    public function getHostCommand($hostname)
    {
        throw new ProgrammingError('This is not supported for single objects');
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string $hostname       The name of the host to insert
     * @param   string $servicename    The name of the service to insert
     * @throws  ProgrammingError
     * @return  string                 The string representation of the command#
     */
    public function getServiceCommand($hostname, $servicename)
    {
        throw new ProgrammingError('This is not supported for single objects');
    }

    /**
     * Create a global command
     *
     * @param   string $instance
     *
     * @return  string
     */
    public function getGlobalCommand($instance = null)
    {
        return sprintf(
            'DISABLE_NOTIFICATIONS_EXPIRE_TIME;%d;%s',
            time(),
            implode(';', $this->getArguments())
        );
    }
}
