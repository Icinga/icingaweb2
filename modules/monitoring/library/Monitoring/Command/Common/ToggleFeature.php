<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Common;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Enable/disable features of the monitoring host
 */
abstract class ToggleFeature extends IcingaCommand
{
    /**
     * Whether the feature should be enabled or disabled
     *
     * @var bool
     */
    protected $enable = true;

    /**
     * Enable the feature
     *
     * @return self
     */
    public function enable()
    {
        $this->enable = true;
        return $this;
    }

    /**
     * Disable the feature
     *
     * @return self
     */
    public function disable()
    {
        $this->enable = false;
        return $this;
    }
}
