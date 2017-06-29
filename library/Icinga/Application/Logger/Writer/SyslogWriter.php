<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Logger\Writer;

use Icinga\Data\ConfigObject;
use Icinga\Application\Logger;
use Icinga\Application\Logger\LogWriter;
use Icinga\Exception\ConfigurationError;

/**
 * Log to the syslog service
 */
class SyslogWriter extends LogWriter
{
    /**
     * Syslog facility
     *
     * @var int
     */
    protected $facility;

    /**
     * Prefix to prepend to each message
     *
     * @var string
     */
    protected $ident;

    /**
     * Known syslog facilities
     *
     * @var array
     */
    public static $facilities = array(
        'user'      => LOG_USER,
        'local0'    => LOG_LOCAL0,
        'local1'    => LOG_LOCAL1,
        'local2'    => LOG_LOCAL2,
        'local3'    => LOG_LOCAL3,
        'local4'    => LOG_LOCAL4,
        'local5'    => LOG_LOCAL5,
        'local6'    => LOG_LOCAL6,
        'local7'    => LOG_LOCAL7
    );

    /**
     * Log level to syslog severity map
     *
     * @var array
     */
    public static $severityMap = array(
        Logger::ERROR   => LOG_ERR,
        Logger::WARNING => LOG_WARNING,
        Logger::INFO    => LOG_INFO,
        Logger::DEBUG   => LOG_DEBUG
    );

    /**
     * Create a new syslog log writer
     *
     * @param   ConfigObject    $config
     */
    public function __construct(ConfigObject $config)
    {
        $this->ident = $config->get('application', 'icingaweb2');

        $configuredFacility = $config->get('facility', 'user');
        if (! isset(static::$facilities[$configuredFacility])) {
            throw new ConfigurationError(
                'Invalid logging facility: "%s" (expected one of: %s)',
                $configuredFacility,
                implode(', ', array_keys(static::$facilities))
            );
        }
        $this->facility = static::$facilities[$configuredFacility];
    }

    /**
     * Log a message
     *
     * @param   int     $level      The logging level
     * @param   string  $message    The log message
     */
    public function log($level, $message)
    {
        openlog($this->ident, LOG_PID, $this->facility);
        syslog(static::$severityMap[$level], str_replace("\n", '    ', $message));
    }
}
