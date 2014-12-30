<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

/**
 * Common string helper
 */
class String
{
    /**
     * Split string into an array and trim spaces
     *
     * @param   string  $value
     * @param   string  $delimiter
     *
     * @return array
     */
    public static function trimSplit($value, $delimiter = ',')
    {
        return array_map('trim', explode($delimiter, $value));
    }

    /**
     * Uppercase the first character of each word in a string
     *
     * Converts 'first_name' to 'firstName' for example.
     *
     * @param   string $name
     * @param   string $separator Word separator
     *
     * @return  string
     */
    public static function cname($name, $separator = '_')
    {
        return str_replace(' ', '', ucwords(str_replace($separator, ' ', strtolower($name))));
    }
}
