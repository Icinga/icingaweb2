<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

/**
 * Rationale:
 *
 * If an exception is being thrown while a confidential string is being used
 * as a function parameter somewhere on the stack, that string shall not be shown in the stack trace.
 */
class StringWrapper
{
    /**
     * The string to be wrapped
     *
     * @var string
     */
    protected $string;

    /**
     * Constructor
     *
     * @param   string  $string The string to be wrapped
     */
    public function __construct($string)
    {
        $this->string = $string;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->string;
    }
}
