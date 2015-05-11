<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Exception;

/**
 * Exception thrown if a mandatory parameter was not given
 */
class MissingParameterException extends IcingaException
{
    /**
     * Name of the missing parameter
     *
     * @var string
     */
    protected $parameter;

    /**
     * Get the name of the missing parameter
     *
     * @return string
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * Set the name of the missing parameter
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setParameter($name)
    {
        $this->parameter = (string) $name;
        return $this;
    }
}
