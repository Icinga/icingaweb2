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
     * Whether the form's inline
     *
     * @var bool
     */
    protected $inline = false;

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
     * Set the form as inline
     *
     * @return $this
     */
    public function inline()
    {
        $this->inline = true;
        $this->submitLabel = null;  // Inline forms must not have a submit button
        $class = $this->getAttrib('class');
        if (is_array($class)) {
            $class[] = 'inline';
        } elseif ($class === null) {
            $class = 'inline';
        } else {
            $class .= 'inline';
        }
        $this->setAttrib('class', $class);
        return $this;
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
