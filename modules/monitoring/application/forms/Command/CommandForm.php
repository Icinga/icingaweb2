<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command;

use Icinga\Exception\ConfigurationError;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\Monitoring\Command\Transport\CommandTransport;
use Icinga\Module\Monitoring\Command\Transport\CommandTransportInterface;

/**
 * Base class for command forms
 */
abstract class CommandForm extends Form
{
    /**
     * Monitoring backend
     *
     * @var MonitoringBackend
     */
    protected $backend;

    /**
     * Set the monitoring backend
     *
     * @param   MonitoringBackend $backend
     *
     * @return  $this
     */
    public function setBackend(MonitoringBackend $backend)
    {
        $this->backend = $backend;
        return $this;
    }

    /**
     * Get the monitoring backend
     *
     * @return MonitoringBackend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Get the transport used to send commands
     *
     * @param   Request     $request
     *
     * @return  CommandTransportInterface
     *
     * @throws  ConfigurationError
     */
    public function getTransport(Request $request)
    {
        if (($transportName = $request->getParam('transport')) !== null) {
            $config = CommandTransport::getConfig();
            if ($config->hasSection($transportName)) {
                $transport = CommandTransport::createTransport($config->getSection($transportName));
            } else {
                throw new ConfigurationError(sprintf(
                    mt('monitoring', 'Command transport "%s" not found.'),
                    $transportName
                ));
            }
        } else {
            $transport = new CommandTransport();
        }

        return $transport;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl()
    {
        $redirectUrl = parent::getRedirectUrl();
        // TODO(el): Forms should provide event handling. This is quite hackish
        $formData = $this->getRequestData();
        if ($this->wasSent($formData)
            && (! $this->getSubmitLabel() || $this->isSubmitted())
            && $this->isValid($formData)
        ) {
            $this->getResponse()->setAutoRefreshInterval(1);
        }
        return $redirectUrl;
    }
}
