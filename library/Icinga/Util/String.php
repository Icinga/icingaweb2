<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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

    /**
     * Find and return all similar strings in $possibilites matching $string with the given minimum $similarity
     *
     * @param   string  $string
     * @param   array   $possibilities
     * @param   float   $similarity
     *
     * @return  array
     */
    public static function findSimilar($string, array $possibilities, $similarity = 0.33)
    {
        if (empty($string)) {
            return array();
        }

        $matches = array();
        foreach ($possibilities as $possibility) {
            $distance = levenshtein($string, $possibility);
            if ($distance / strlen($string) <= $similarity) {
                $matches[] = $possibility;
            }
        }

        return $matches;
    }
}
