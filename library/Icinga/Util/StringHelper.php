<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

/**
 * Common string functions
 */
class StringHelper
{
    /**
     * Split string into an array and trim spaces
     *
     * @param   string  $value
     * @param   string  $delimiter
     * @param   int     $limit
     *
     * @return array
     */
    public static function trimSplit($value, $delimiter = ',', $limit = null)
    {
        if ($limit !== null) {
            $exploded = explode($delimiter, $value, $limit);
        } else {
            $exploded = explode($delimiter, $value);
        }

        return array_map('trim', $exploded);
    }

    /**
     * Uppercase the first character of each word in a string
     *
     * Converts 'first_name' to 'FirstName' for example.
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
     * Add ellipsis in the center of a string when a string is longer than max length
     *
     * @param   string  $string
     * @param   int     $maxLength
     * @param   string  $ellipsis
     *
     * @return  string
     */
    public static function ellipsisCenter($string, $maxLength, $ellipsis = '...')
    {
        $start = ceil($maxLength / 2.0);
        $end = floor($maxLength / 2.0);
        if (strlen($string) > $maxLength) {
            return substr($string, 0, $start - strlen($ellipsis)) . $ellipsis . substr($string, - $end);
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

    /**
     * Test whether the given string ends with the given suffix
     *
     * @param   string  $string The string to test
     * @param   string  $suffix The suffix the string must end with
     *
     * @return bool
     */
    public static function endsWith($string, $suffix)
    {
        $stringSuffix = substr($string, -strlen($suffix));
        return $stringSuffix !== false ? $stringSuffix === $suffix : false;
    }

    /**
     * Generates an array of strings that constitutes the cartesian product of all passed sets, with all
     * string combinations concatenated using the passed join-operator.
     *
     * <pre>
     *  cartesianProduct(
     *      array(array('foo', 'bar'), array('mumble', 'grumble', null)),
     *      '_'
     *  );
     *     => array('foo_mumble', 'foo_grumble', 'bar_mumble', 'bar_grumble', 'foo', 'bar')
     * </pre>
     *
     * @param   array   $sets   An array of arrays containing all sets for which the cartesian
     *                          product should be calculated.
     * @param   string  $glue   The glue used to join the strings, defaults to ''.
     *
     * @returns array           The cartesian product in one array of strings.
     */
    public static function cartesianProduct(array $sets, $glue = '')
    {
        $product = null;
        foreach ($sets as $set) {
            if (! isset($product)) {
                $product = $set;
            } else {
                $newProduct = array();
                foreach ($product as $strA) {
                    foreach ($set as $strB) {
                        if ($strB === null) {
                            $newProduct []= $strA;
                        } else {
                            $newProduct []= $strA . $glue . $strB;
                        }
                    }
                }
                $product = $newProduct;
            }
        }
        return $product;
    }
}
