<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Exception;

use Icinga\Exception\IcingaException;

/**
 * Exception thrown if {@link curl_exec()} fails
 */
class CurlException extends IcingaException
{
}
