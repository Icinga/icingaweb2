<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Logger\Writer;

use Icinga\Application\Logger;
use Icinga\Application\Logger\LogWriter;
use Icinga\Data\ConfigObject;
use Icinga\Exception\NotWritableError;

/**
 * Log to the webserver log, a file or syslog
 *
 * @see https://secure.php.net/manual/en/errorfunc.configuration.php#ini.error-log
 */
class PhpWriter extends LogWriter
{
    /**
     * Prefix to prepend to each message
     *
     * @var string
     */
    protected $ident;

    public function __construct(ConfigObject $config)
    {
        parent::__construct($config);
        $this->ident = $config->get('application', 'icingaweb2');
    }

    public function log($severity, $message)
    {
        if (ini_get('error_log') === 'syslog') {
            $message = str_replace("\n", '    ', $message);
        }

        error_log($this->ident . ': ' . Logger::$levels[$severity] . ' - ' . $message);
    }
}
