<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Exception;

use Exception;
use ReflectionClass;
use Throwable;

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
            if ($arg instanceof Throwable) {
                $exc = $arg;
            }
        }

        if (! empty($args)) {
            $message = vsprintf($message, $args);
        }

        parent::__construct($message, 0, $exc);
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
     * @param   Throwable   $exception
     *
     * @return  string
     */
    public static function describe(Throwable $exception)
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
     * @param   Throwable   $exception
     *
     * @return  string
     */
    public static function getConfidentialTraceAsString(Throwable $exception)
    {
        $trace = [];

        $index = 0;
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
                $args = [];
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
