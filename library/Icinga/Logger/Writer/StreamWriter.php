<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Logger\Writer;

use Exception;
use Zend_Config;
use Icinga\Logger\Logger;
use Icinga\Logger\LogWriter;
use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;

/**
 * Class to write log messages to a stream
 */
class StreamWriter extends LogWriter
{
    /**
     * The path to the stream
     *
     * @var string
     */
    protected $stream;

    /**
     * Create a new log writer initialized with the given configuration
     */
    public function __construct(Zend_Config $config)
    {
        $this->stream = Config::resolvePath($config->target);
        $this->setup();
    }

    /**
     * Log a message with the given severity
     *
     * @param   int     $severity   The severity to use
     * @param   string  $message    The message to log
     */
    public function log($severity, $message)
    {
        $this->write(date('c') . ' ' . $this->getSeverityString($severity) . ' ' . $message);
    }

    /**
     * Create the stream if it does not already exist
     */
    protected function setup()
    {
        if (substr($this->stream, 0, 6) !== 'php://') {
            if (!file_exists($this->stream) && (!@touch($this->stream) || !@chmod($this->stream, 0664))) {
                throw new ConfigurationError('Cannot create log file "' . $this->stream . '"');
            }

            if (!@is_writable($this->stream)) {
                throw new ConfigurationError('Cannot write to log file "' . $this->stream . '"');
            }
        }
    }

    /**
     * Return a string representation for the given severity
     *
     * @param   string      $severity   The severity to use
     *
     * @return  string                  The string representation of the severity
     *
     * @throws  Exception               In case the given severity is unknown
     */
    protected function getSeverityString($severity)
    {
        switch ($severity) {
            case Logger::$ERROR:
                return '- ERROR -';
            case Logger::$WARNING:
                return '- WARNING -';
            case Logger::$INFO:
                return '- INFO -';
            case Logger::$DEBUG:
                return '- DEBUG -';
            default:
                throw new Exception('Unknown severity "' . $severity . '"');
        }
    }

    /**
     * Write a message to the stream
     *
     * @param   string  $text   The message to write
     *
     * @throws  Exception       In case write acess to the stream failed
     */
    protected function write($text)
    {
        $fd = fopen($this->stream, 'a');

        if ($fd === false || fwrite($fd, $text . PHP_EOL) === false) {
            throw new Exception('Failed to write to log file "' . $this->stream . '"');
        }

        fclose($fd);
    }
}
