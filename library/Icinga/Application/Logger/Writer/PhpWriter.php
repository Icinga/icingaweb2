<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Logger\Writer;

use Icinga\Application\Logger;
use Icinga\Application\Logger\LogWriter;
use Icinga\Exception\NotWritableError;

/**
 * Log to the webserver log, a file or syslog
 *
 * @see https://secure.php.net/manual/en/errorfunc.configuration.php#ini.error-log
 */
class PhpWriter extends LogWriter
{
    /**
     * {@inheritdoc}
     */
    public function log($severity, $message)
    {
        if (! error_log(Logger::$levels[$severity] . ' - ' . str_replace("\n", '    ', $message))) {
            throw new NotWritableError('Could not log to ' . (ini_get('error_log') ?: 'SAPI'));
        }
    }
}
