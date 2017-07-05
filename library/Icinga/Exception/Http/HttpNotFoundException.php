<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Exception\Http;

/**
 * Exception thrown for sending a HTTP 404 response w/ a custom message
 */
class HttpNotFoundException extends BaseHttpException
{
    protected $statusCode = 404;
}
