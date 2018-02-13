<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Exception;

use Exception;
use ReflectionClass;

class IcingaException extends Exception
{
    /**
     * Create a new exception
     *
     * @param   string  $message    Exception message or exception format string
     * @param   mixed   ...$arg     Format string argument
     *
     * If there is at least one exception, the last one will be used for exception chaining.
     */
    public function __construct($message)
    {
        $args = array_slice(func_get_args(), 1);
        $exc = null;
        foreach ($args as &$arg) {
            if ($arg instanceof Exception) {
                $exc = $arg;
            }
        }
        parent::__construct(vsprintf($message, $args), 0, $exc);
    }

    /**
     * Create the exception from an array of arguments
     *
     * @param   array   $args
     *
     * @return  static
     */
    public static function create(array $args)
    {
        $e = new ReflectionClass(get_called_class());
        return $e->newInstanceArgs($args);
    }

    /**
     * Return the given exception formatted as one-liner
     *
     * The format used is: %class% in %path%:%line% with message: %message%
     *
     * @param   Exception   $exception
     *
     * @return  string
     */
    public static function describe(Exception $exception)
    {
        return sprintf(
            '%s in %s:%d with message: %s',
            get_class($exception),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage()
        );
    }

    /**
     * Return the same as {@link Exception::getTraceAsString()} for the given exception,
     * but show only the types of scalar arguments
     *
     * @param   Exception   $exception
     *
     * @return  string
     */
    public static function getConfidentialTraceAsString(Exception $exception)
    {
        $trace = array();

        foreach ($exception->getTrace() as $index => $frame) {
            $trace[] = isset($frame['file'])
                ? "#{$index} {$frame['file']}({$frame['line']}): "
                : "#{$index} [internal function]: ";

            if (isset($frame['class'])) {
                $trace[] = $frame['class'];
            }

            if (isset($frame['type'])) {
                $trace[] = $frame['type'];
            }

            $trace[] = "{$frame['function']}(";

            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    $type = gettype($arg);
                    $args[] = $type === 'object' ? 'Object(' . get_class($arg) . ')' : ucfirst($type);
                }

                $trace[] = implode(', ', $args);
            }
            $trace[] = ")\n";
        }

        $trace[] = '#' . ($index + 1) . ' {main}';

        return implode($trace);
    }
}
