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

    /**
     * {@inheritDoc}
     */
    public function __construct(ConfigObject $config)
    {
        parent::__construct($config);
        $this->ident = $config->get('application', 'icingaweb2');
    }

    /**
     * {@inheritdoc}
     */
    public function log($severity, $message)
    {
        if (! error_log($this->ident . ': ' . Logger::$levels[$severity] . ' - ' . (
            ini_get('error_log') === 'syslog' ? str_replace("\n", '    ', $message) : $message
        ))) {
            throw new NotWritableError('Could not log to ' . (ini_get('error_log') ?: 'SAPI'));
        }
    }
}
