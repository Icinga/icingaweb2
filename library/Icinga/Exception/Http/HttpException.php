<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Exception\Http;

class HttpException extends BaseHttpException
{
    /**
     * Create a new HttpException
     *
     * @param   int     $statusCode     HTTP status code
     * @param   string  $message        Exception message or exception format string
     * @param   mixed   ...$arg         Format string argument
     *
     * If there is at least one exception, the last one will be used for exception chaining.
     */
    public function __construct($statusCode, $message)
    {
        $this->statusCode = (int) $statusCode;

        $args = func_get_args();
        array_shift($args);
        call_user_func_array('parent::__construct', $args);
    }
}
