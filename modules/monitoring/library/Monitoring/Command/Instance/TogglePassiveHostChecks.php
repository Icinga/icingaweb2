<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Instance;

use Icinga\Module\Monitoring\Command\Common\ToggleFeature;

/**
 * Enable/disable passive host checks on an Icinga instance
 */
class TogglePassiveHostChecks extends ToggleFeature
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        return $this->enable === true ? 'ENABLE_PASSIVE_HOST_CHECKS' : 'DISABLE_PASSIVE_HOST_CHECKS';
    }
}
