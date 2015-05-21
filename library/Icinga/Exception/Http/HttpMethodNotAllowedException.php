<?php

namespace Icinga\Exception\Http;

use Icinga\Exception\IcingaException;

/**
 * Exception thrown if the HTTP method is not allowed
 */
class HttpMethodNotAllowedException extends IcingaException
{
    /**
     * Allowed HTTP methods
     *
     * @var string
     */
    protected $allowedMethods;

    /**
     * Get the allowed HTTP methods
     *
     * @return string
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }

    /**
     * Set the allowed HTTP methods
     *
     * @param   string $allowedMethods
     *
     * @return  $this
     */
    public function setAllowedMethods($allowedMethods)
    {
        $this->allowedMethods = (string) $allowedMethods;
        return $this;
    }
}
