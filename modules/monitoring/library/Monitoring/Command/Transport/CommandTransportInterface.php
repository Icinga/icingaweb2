<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Interface for Icinga command transports
 */
interface CommandTransportInterface
{
    /**
     * Send an Icinga command over the Icinga command transport
     *
     * @param   IcingaCommand   $command    The command to send
     * @param   int|null        $now        Timestamp of the command or null for now
     *
     * @throws  \Icinga\Module\Monitoring\Exception\CommandTransportException If sending the Icinga command failed
     */
    public function send(IcingaCommand $command, $now = null);
}
