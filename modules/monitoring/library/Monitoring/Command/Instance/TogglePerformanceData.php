<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Instance;

use Icinga\Module\Monitoring\Command\Common\ToggleFeature;

/**
 * Enable/disable the processing of host and service performance data on an Icinga instance
 */
class TogglePerformanceData extends ToggleFeature
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        return $this->enable === true ? 'ENABLE_PERFORMANCE_DATA' : 'DISABLE_PERFORMANCE_DATA';
    }
}
