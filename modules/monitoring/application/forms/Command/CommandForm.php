<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Command\Transport\CommandTransport;
use Icinga\Web\Form;
use Icinga\Web\Request;

/**
 * Base class for command forms
 */
abstract class CommandForm extends Form
{
    /**
     * Monitoring backend
     *
     * @var Backend
     */
    protected $backend;

    /**
     * Set the monitoring backend
     *
     * @param   Backend $backend
     *
     * @return  $this
     */
    public function setBackend(Backend $backend)
    {
        $this->backend = $backend;
        return $this;
    }

    /**
     * Get the monitoring backend
     *
     * @return Backend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Get the transport used to send commands
     *
     * @param   Request $request
     *
     * @return  \Icinga\Module\Monitoring\Command\Transport\CommandTransportInterface
     */
    public function getTransport(Request $request)
    {
        $instance = $request->getParam('instance');
        if ($instance !== null) {
            $transport = CommandTransport::create($instance);
        } else {
            $transport = CommandTransport::first();
        }
        return $transport;
    }
}
