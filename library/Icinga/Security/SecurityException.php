<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Security;

use Icinga\Exception\IcingaException;

/**
 * Exception thrown when a caller does not have the permissions required to access a resource
 */
class SecurityException extends IcingaException
{
}
