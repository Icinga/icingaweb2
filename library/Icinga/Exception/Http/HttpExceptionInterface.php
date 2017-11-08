<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Exception\Http;

interface HttpExceptionInterface
{
    /**
     * Return this exception's HTTP status code
     *
     * @return  int
     */
    public function getStatusCode();

    /**
     * Return this exception's HTTP response headers
     *
     * @return  array   An array where each key is a header name and the value its value
     */
    public function getHeaders();
}
