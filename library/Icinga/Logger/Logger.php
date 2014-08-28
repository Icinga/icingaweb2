<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Logger;

use Exception;
use Zend_Config;
use LogicException;
use Icinga\Exception\ConfigurationError;

/**
 * Singleton logger
 */
class Logger
{
    /**
     * This logger's instance
     *
     * @var Logger
     */
    protected static $instance;

    /**
     * The log writer to use
     *
     * @var LogWriter
     */
    protected $writer;

    /**
     * The maximum severity to emit
     *
     * @var int
     */
    protected $verbosity;

    /**
     * The supported severities
     */
    public static $NONE = 0;
    public static $ERROR = 1;
    public static $WARNING = 2;
    public static $INFO = 3;
    public static $DEBUG = 4;

    /**
     * Create a new logger object
     *
     * @param   Zend_Config     $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->verbosity = $config->level;

        if ($config->enable) {
            $this->writer = $this->getWriter($config);
        }
    }

    /**
     * Create a new logger object
     *
     * @param   Zend_Config     $config
     */
    public static function create(Zend_Config $config)
    {
        static::$instance = new static($config);
    }

    /**
     * Return a log writer
     *
     * @param   Zend_Config     $config     The configuration to initialize the writer with
     *
     * @return  LogWriter                   The requested log writer
     *
     * @throws  ConfigurationError          In case the requested writer cannot be found
     */
    protected function getWriter(Zend_Config $config)
    {
        $class = 'Icinga\\Logger\\Writer\\' . ucfirst(strtolower($config->type)) . 'Writer';
        if (!class_exists($class)) {
            throw new ConfigurationError(
                'Cannot find log writer of type "%s"',
                $config->type
            );
        }

        return new $class($config);
    }

    /**
     * Write a message to the log
     *
     * @param   string  $message    The message to write
     * @param   int     $severity   The severity to use
     *
     * @throws  LogicException      In case $severity equals self::$NONE
     */
    public function log($message, $severity)
    {
        if ($severity === static::$NONE) {
            throw new LogicException("`None' (0) is not a valid severity to log messages");
        }

        if ($this->writer !== null && $this->verbosity >= $severity) {
            $this->writer->log($severity, $message);
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
            static::$instance->log(static::formatMessage(func_get_args()), static::$ERROR);
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
            static::$instance->log(static::formatMessage(func_get_args()), static::$WARNING);
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
            static::$instance->log(static::formatMessage(func_get_args()), static::$INFO);
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
            static::$instance->log(static::formatMessage(func_get_args()), static::$DEBUG);
        }
    }
}
