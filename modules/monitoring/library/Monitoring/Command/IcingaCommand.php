<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command;

/**
 * Base class for commands sent to an Icinga instance
 */
abstract class IcingaCommand
{
    /**
     * Name of the instance for this command
     *
     * @var string
     */
    protected $instance;

    /**
     * Get the name of the command
     *
     * @return string
     */
    public function getName()
    {
        $nsParts = explode('\\', get_called_class());
        return substr_replace(end($nsParts), '', -7);  // Remove 'Command' Suffix
    }

    /**
     * Get the instance name
     *
     * @return string
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Set the instance name
     *
     * @param string $instance
     *
     * @return $this
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }
}
