<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Instance;

use Icinga\Module\Monitoring\Command\Common\ToggleFeature;

/**
 * Enable/disable host and service event handlers on an Icinga instance
 */
class ToggleEventHandlers extends ToggleFeature
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        return $this->enable === true ? 'ENABLE_EVENT_HANDLERS' : 'DISABLE_EVENT_HANDLERS';
    }
}
