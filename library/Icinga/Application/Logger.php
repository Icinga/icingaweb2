<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Exception;
use Zend_Config;
use Icinga\Application\Logger\Writer\FileWriter;
use Icinga\Application\Logger\Writer\SyslogWriter;
use Icinga\Exception\ConfigurationError;

/**
 * Logger
 */
class Logger
{
    /**
     * Debug message
     */
    const DEBUG = 1;

    /**
     * Informational message
     */
    const INFO = 2;

    /**
     * Warning message
     */
    const WARNING = 4;

    /**
     * Error message
     */
    const ERROR = 8;

    /**
     * Log levels
     *
     * @var array
     */
    public static $levels = array(
        Logger::DEBUG   => 'DEBUG',
        Logger::INFO    => 'INFO',
        Logger::WARNING => 'WARNING',
        Logger::ERROR   => 'ERROR'
    );

    /**
     * This logger's instance
     *
     * @var static
     */
    protected static $instance;

    /**
     * Log writer
     *
     * @var \Icinga\Application\Logger\LogWriter
     */
    protected $writer;

    /**
     * Maximum level to emit
     *
     * @var int
     */
    protected $level;

    /**
     * Create a new logger object
     *
     * @param   Zend_Config $config
     *
     * @throws  ConfigurationError  If the logging configuration directive 'log' is missing or if the logging level is
     *                              not defined
     */
    public function __construct(Zend_Config $config)
    {
        if ($config->log === null) {
            throw new ConfigurationError('Required logging configuration directive \'log\' missing');
        }

        if (($level = $config->level) !== null) {
            if (is_numeric($level)) {
                $level = (int) $level;
                if (! isset(static::$levels[$level])) {
                    throw new ConfigurationError(
                        'Can\'t set logging level %d. Logging level is not defined. Use one of %s or one of the'
                        . ' Logger\'s constants.',
                        $level,
                        implode(', ', array_keys(static::$levels))
                    );
                }
                $this->level = $level;
            } else {
                $level = strtoupper($level);
                $levels = array_flip(static::$levels);
                if (! isset($levels[$level])) {
                    throw new ConfigurationError(
                        'Can\'t set logging level "%s". Logging level is not defined. Use one of %s.',
                        $level,
                        implode(', ', array_keys($levels))
                    );
                }
                $this->level = $levels[$level];
            }
        } else {
            $this->level = static::ERROR;
        }

        if (strtolower($config->get('log', 'syslog')) !== 'none') {
            $this->writer = $this->createWriter($config);
        }
    }

    /**
     * Create a new logger object
     *
     * @param   Zend_Config     $config
     *
     * @return  static
     */
    public static function create(Zend_Config $config)
    {
        static::$instance = new static($config);
        return static::$instance;
    }

    /**
     * Create a log writer
     *
     * @param   Zend_Config     $config     The configuration to initialize the writer with
     *
     * @return  \Icinga\Application\Logger\LogWriter    The requested log writer
     * @throws  ConfigurationError                      If the requested writer cannot be found
     */
    protected function createWriter(Zend_Config $config)
    {
        $class = 'Icinga\\Application\\Logger\\Writer\\' . ucfirst(strtolower($config->log)) . 'Writer';
        if (! class_exists($class)) {
            throw new ConfigurationError(
                'Cannot find log writer of type "%s"',
                $config->log
            );
        }
        return new $class($config);
    }

    /**
     * Log a message
     *
     * @param   int     $level      The logging level
     * @param   string  $message    The log message
     */
    public function log($level, $message)
    {
        if ($this->writer !== null && $this->level <= $level) {
            $this->writer->log($level, $message);
        }
    }

    /**
     * Return a string representation of the passed arguments
     *
     * This method provides three different processing techniques:
     *  - If the only passed argument is a string it is returned unchanged
     *  - If the only passed argument is an exception it is formatted as follows:
     *    <name> in <file>:<line> with message: <message>[ <- <name> ...]
     *  - If multiple arguments are passed the first is interpreted as format-string
     *    that gets substituted with the remaining ones which can be of any type
     *
     * @param   array   $arguments      The arguments to format
     *
     * @return  string                  The formatted result
     */
    protected static function formatMessage(array $arguments)
    {
        if (count($arguments) === 1) {
            $message = $arguments[0];

            if ($message instanceof Exception) {
                $messages = array();
                $error = $message;
                do {
                    $messages[] = sprintf(
                        '%s in %s:%d with message: %s',
                        get_class($error),
                        $error->getFile(),
                        $error->getLine(),
                        $error->getMessage()
                    );
                } while ($error = $error->getPrevious());
                $message = implode(' <- ', $messages);
            }

            return $message;
        }

        return vsprintf(
            array_shift($arguments),
            array_map(
                function ($a) { return is_string($a) ? $a : json_encode($a); },
                $arguments
            )
        );
    }

    /**
     * Log a message with severity ERROR
     *
     * @param   mixed   $arg,...    A string, exception or format-string + substitutions
     */
    public static function error()
    {
        if (static::$instance !== null && func_num_args() > 0) {
            static::$instance->log(static::ERROR, static::formatMessage(func_get_args()));
        }
    }

    /**
     * Log a message with severity WARNING
     *
     * @param   mixed   $arg,...    A string, exception or format-string + substitutions
     */
    public static function warning()
    {
        if (static::$instance !== null && func_num_args() > 0) {
            static::$instance->log(static::WARNING, static::formatMessage(func_get_args()));
        }
    }

    /**
     * Log a message with severity INFO
     *
     * @param   mixed   $arg,...    A string, exception or format-string + substitutions
     */
    public static function info()
    {
        if (static::$instance !== null && func_num_args() > 0) {
            static::$instance->log(static::INFO, static::formatMessage(func_get_args()));
        }
    }

    /**
     * Log a message with severity DEBUG
     *
     * @param   mixed   $arg,...    A string, exception or format-string + substitutions
     */
    public static function debug()
    {
        if (static::$instance !== null && func_num_args() > 0) {
            static::$instance->log(static::DEBUG, static::formatMessage(func_get_args()));
        }
    }

    /**
     * Get the log writer to use
     *
     * @return \Icinga\Application\Logger\LogWriter
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * Is the logger writing to Syslog?
     *
     * @return bool
     */
    public static function writesToSyslog()
    {
        return static::$instance && static::$instance instanceof SyslogWriter;
    }

    /**
     * Is the logger writing to a file?
     *
     * @return bool
     */
    public static function writesToFile()
    {
        return static::$instance && static::$instance instanceof FileWriter;
    }

    /**
     * Get this' instance
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }
}
