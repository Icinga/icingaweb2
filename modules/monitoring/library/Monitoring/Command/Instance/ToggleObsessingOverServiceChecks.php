<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Instance;

use Icinga\Module\Monitoring\Command\Common\ToggleFeature;

/**
 * Enable/disable processing of service checks via the OCHP command on an Icinga instance
 */
class ToggleObsessingOverServiceChecks extends ToggleFeature
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        return $this->enable === true ? 'START_OBSESSING_OVER_SVC_CHECKS' : 'STOP_OBSESSING_OVER_SVC_CHECKS';
    }
}
