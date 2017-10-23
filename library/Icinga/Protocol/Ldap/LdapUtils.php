<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

/**
 * This class provides useful LDAP-related functions
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.com>
 * @author     Icinga-Web Team <info@icinga.com>
 * @package    Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class LdapUtils
{
    /**
     * Extends PHPs ldap_explode_dn() function
     *
     * UTF-8 chars like German umlauts would otherwise be escaped and shown
     * as backslash-prefixed hexcode-sequenzes.
     *
     * @param  string  $dn        DN
     * @param  boolean $with_type Returns 'type=value' when true and 'value' when false
     *
     * @return string
     */
    public static function explodeDN($dn, $with_type = true)
    {
        $res = ldap_explode_dn($dn, $with_type ? 0 : 1);

        foreach ($res as $k => $v) {
            $res[$k] = preg_replace_callback(
                '/\\\([0-9a-f]{2})/i',
                function ($m) {
                    return chr(hexdec($m[1]));
                },
                $v
            );
        }
        unset($res['count']);
        return $res;
    }

    /**
     * Implode unquoted RDNs to a DN
     *
     * TODO: throw away, this is not how it shall be done
     *
     * @param  array $parts DN-component
     *
     * @return string
     */
    public static function implodeDN($parts)
    {
        $str = '';
        foreach ($parts as $part) {
            if ($str !== '') {
                $str .= ',';
            }
            list($key, $val) = preg_split('~=~', $part, 2);
            $str .= $key . '=' . self::quoteForDN($val);
        }
        return $str;
    }

    /**
     * Test if supplied value looks like a DN
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public static function isDn($value)
    {
        if (is_string($value)) {
            return ldap_dn2ufn($value) !== false;
        }
        return false;
    }

    /**
     * Quote a string that should be used in a DN
     *
     * Special characters will be escaped
     *
     * @param  string $str DN-component
     *
     * @return string
     */
    public static function quoteForDN($str)
    {
        return self::quoteChars(
            $str,
            array(
                ',',
                '=',
                '+',
                '<',
                '>',
                ';',
                '\\',
                '"',
                '#'
            )
        );
    }

    /**
     * Quote a string that should be used in an LDAP search
     *
     * Special characters will be escaped
     *
     * @param  string String to be escaped
     * @param bool $allow_wildcard
     * @return string
     */
    public static function quoteForSearch($str, $allow_wildcard = false)
    {
        if ($allow_wildcard) {
            return self::quoteChars($str, array('(', ')', '\\', chr(0)));
        }
        return self::quoteChars($str, array('*', '(', ')', '\\', chr(0)));
    }

    /**
     * Escape given characters in the given string
     *
     * Special characters will be escaped
     *
     * @param $str
     * @param $chars
     * @internal param String $string to be escaped
     * @return string
     */
    protected static function quoteChars($str, $chars)
    {
        $quotedChars = array();
        foreach ($chars as $k => $v) {
            // Temporarily prefixing with illegal '('
            $quotedChars[$k] = '(' . str_pad(dechex(ord($v)), 2, '0');
        }
        $str = str_replace($chars, $quotedChars, $str);
        // Replacing temporary '(' with '\\'. This is a workaround, as
        // str_replace behaves pretty strange with leading a backslash:
        $str = preg_replace('~\(~', '\\', $str);
        return $str;
    }
}
