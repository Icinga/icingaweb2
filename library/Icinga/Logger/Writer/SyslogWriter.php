<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Logger\Writer;

use Exception;
use Zend_Config;
use Icinga\Logger\Logger;
use Icinga\Logger\LogWriter;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\IcingaException;

/**
 * Class to write messages to syslog
 */
class SyslogWriter extends LogWriter
{
    /**
     * The facility where to write messages to
     *
     * @var string
     */
    protected $facility;

    /**
     * The prefix to prepend to each message
     *
     * @var string
     */
    protected $ident;

    /**
     * Known syslog facilities
     *
     * @var array
     */
    protected $facilities = array(
        'LOG_USER' => LOG_USER
    );

    /**
     * Create a new log writer initialized with the given configuration
     */
    public function __construct(Zend_Config $config)
    {
        if (!array_key_exists($config->facility, $this->facilities)) {
            throw new ConfigurationError(
                'Cannot create syslog writer with unknown facility "%s"',
                $config->facility
            );
        }

        $this->ident = $config->application;
        $this->facility = $this->facilities[$config->facility];
    }

    /**
     * Log a message with the given severity
     *
     * @param   int     $severity   The severity to use
     * @param   string  $message    The message to log
     *
     * @throws  Exception           In case the given severity cannot be mapped to a valid syslog priority
     */
    public function log($severity, $message)
    {
        $priorities = array(
            Logger::$ERROR      => LOG_ERR,
            Logger::$WARNING    => LOG_WARNING,
            Logger::$INFO       => LOG_INFO,
            Logger::$DEBUG      => LOG_DEBUG
        );

        if (!array_key_exists($severity, $priorities)) {
            throw new IcingaException(
                'Severity "%s" cannot be mapped to a valid syslog priority',
                $severity
            );
        }

        $this->open();
        $this->write($priorities[$severity], $message);
        $this->close();
    }

    /**
     * Open a new syslog connection
     */
    protected function open()
    {
        openlog($this->ident, 0, $this->facility);
    }

    /**
     * Write a message to the syslog connection
     *
     * @param   int     $priority   The priority to use
     * @param   string  $message    The message to write
     */
    protected function write($priority, $message)
    {
        syslog($priority, $message);
    }

    /**
     * Close the syslog connection
     */
    protected function close()
    {
        closelog();
    }
}
