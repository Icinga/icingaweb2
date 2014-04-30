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
 * Class to write log messages to a file
 */
class FileWriter extends LogWriter
{
    /**
     * The path to the file
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new log writer initialized with the given configuration
     */
    public function __construct(Zend_Config $config)
    {
        $this->path = Config::resolvePath($config->target);
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
     * Create the file if it does not already exist
     */
    protected function setup()
    {
        if (substr($this->path, 0, 6) !== 'php://') {
            if (!file_exists($this->path) && (!@touch($this->path) || !@chmod($this->path, 0664))) {
                throw new ConfigurationError('Cannot create log file "' . $this->path . '"');
            }

            if (!@is_writable($this->path)) {
                throw new ConfigurationError('Cannot write to log file "' . $this->path . '"');
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
     * Write a message to the path
     *
     * @param   string  $text   The message to write
     *
     * @throws  Exception       In case write acess to the path failed
     */
    protected function write($text)
    {
        $fd = fopen($this->path, 'a');

        if ($fd === false || fwrite($fd, $text . PHP_EOL) === false) {
            throw new Exception('Failed to write to log file "' . $this->path . '"');
        }

        fclose($fd);
    }
}
