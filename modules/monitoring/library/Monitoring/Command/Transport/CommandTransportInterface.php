<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Interface for Icinga command transports
 */
interface CommandTransportInterface
{
    /**
     * Send the command
     *
     * @param   IcingaCommand $command
     *
     * @throws  \Icinga\Module\Monitoring\Command\Exception\TransportException
     */
    public function send(IcingaCommand $command);
}
