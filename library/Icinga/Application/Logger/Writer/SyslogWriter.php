<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Logger\Writer;

use Zend_Config;
use Icinga\Application\Logger;
use Icinga\Application\Logger\LogWriter;

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
        'user' => LOG_USER
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
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->ident = $config->get('application', 'icingaweb');
        $this->facility = static::$facilities['user'];
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
        syslog(static::$severityMap[$level], $message);
    }
}
