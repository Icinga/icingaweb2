<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Exception\Http;

use Icinga\Exception\IcingaException;

/**
 * Base class for HTTP exceptions
 */
class BaseHttpException extends IcingaException implements HttpExceptionInterface
{
    /**
     * This exception's HTTP status code
     *
     * @var int
     */
    protected $statusCode;

    /**
     * This exception's HTTP response headers
     *
     * @var array
     */
    protected $headers;

    /**
     * Return this exception's HTTP status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set this exception's HTTP response headers
     *
     * @param   array   $headers
     *
     * @return  $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Set/Add a HTTP response header
     *
     * @param   string  $name
     * @param   string  $value
     *
     * @return  $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Return this exception's HTTP response headers
     *
     * @return array    An array where each key is a header name and the value its value
     */
    public function getHeaders()
    {
        return $this->headers ?: array();
    }
}
