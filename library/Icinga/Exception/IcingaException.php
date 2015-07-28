<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Exception;

use Exception;
use ReflectionClass;

class IcingaException extends Exception
{
    /**
     * @param string $message   format string for vsprintf()
     * Any futher args:         args for vsprintf()
     * @see vsprintf
     *
     * If there is at least one exception, the last one will be also used for the exception chaining.
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
}
