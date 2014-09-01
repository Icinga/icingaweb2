<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Common;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Enable/disable features of an Icinga instance
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
     * @return $this
     */
    public function enable()
    {
        $this->enable = true;
        return $this;
    }

    /**
     * Disable the feature
     *
     * @return $this
     */
    public function disable()
    {
        $this->enable = false;
        return $this;
    }
}
