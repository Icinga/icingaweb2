<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Logger;

use Zend_Config;

/**
 * Abstract class for writers that write messages to a log
 */
abstract class LogWriter
{
    /**
     * Create a new log writer initialized with the given configuration
     */
    abstract public function __construct(Zend_Config $config);

    /**
     * Log a message with the given severity
     */
    abstract public function log($severity, $message);
}
