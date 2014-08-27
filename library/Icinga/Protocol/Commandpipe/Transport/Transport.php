<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe\Transport;

use \Zend_Config;

/**
 * Interface for Transport classes handling the concrete access to the command pipe
 */
interface Transport
{
    /**
     * Overwrite the target file of this Transport class using the given config from instances.ini
     *
     * @param Zend_Config $config A configuration file containing a 'path' setting
     */
    public function setEndpoint(Zend_Config $config);

    /**
     * Write the given external command to the command pipe
     *
     * @param string $message The command to send, without the timestamp (this will be added here)
     */
    public function send($message);
}
