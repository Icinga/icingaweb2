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
     * Uppercase the first character of each word in a string assuming and removing the underscore as word separator
     *
     * Converts 'first_name' to 'firstName' for example.
     *
     * @param   string $name
     *
     * @return  string
     */
    public static function cname($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($name))));
    }
}
