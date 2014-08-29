<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Logger\Writer;

use Exception;
use Icinga\Exception\IcingaException;
use Zend_Config;
use Icinga\Util\File;
use Icinga\Logger\Logger;
use Icinga\Logger\LogWriter;
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
     *
     * @throws  ConfigurationError      In case the log path does not exist
     */
    public function __construct(Zend_Config $config)
    {
        $this->path = $config->target;

        if (substr($this->path, 0, 6) !== 'php://' && false === file_exists(dirname($this->path))) {
            throw new ConfigurationError(
                'Log path "%s" does not exist',
                dirname($this->path)
            );
        }

        try {
            $this->write(''); // Avoid to handle such errors on every write access
        } catch (Exception $e) {
            throw new ConfigurationError(
                'Cannot write to log file "%s" (%s)',
                $this->path,
                $e->getMessage()
            );
        }
    }

    /**
     * Log a message with the given severity
     *
     * @param   int     $severity   The severity to use
     * @param   string  $message    The message to log
     */
    public function log($severity, $message)
    {
        $this->write(date('c') . ' ' . $this->getSeverityString($severity) . ' ' . $message . PHP_EOL);
    }

    /**
     * Return a string representation for the given severity
     *
     * @param   string      $severity   The severity to use
     *
     * @return  string                  The string representation of the severity
     *
     * @throws  IcingaException         In case the given severity is unknown
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
                throw new IcingaException(
                    'Unknown severity "%s"',
                    $severity
                );
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
        $file = new File($this->path, 'a');
        $file->fwrite($text);
        $file->fflush();
    }
}
