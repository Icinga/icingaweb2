<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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

    /**
     * Add ellipsis when a string is longer than max length
     *
     * @param   string  $string
     * @param   int     $maxLength
     * @param   string  $ellipsis
     *
     * @return  string
     */
    public static function ellipsis($string, $maxLength, $ellipsis = '...')
    {
        if (strlen($string) > $maxLength) {
            return substr($string, 0, $maxLength - strlen($ellipsis)) . $ellipsis;
        }

        return $string;
    }
}
