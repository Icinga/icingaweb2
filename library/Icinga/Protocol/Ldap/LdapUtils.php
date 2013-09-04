<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Ldap;

/**
 * This class provides useful LDAP-related functions
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
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
     * @param  string  DN
     * @param  boolean Returns 'type=value' when true and 'value' when false
     * @return string
     */
    public static function explodeDN($dn, $with_type = true)
    {
        $res = ldap_explode_dn($dn, $with_type ? 0 : 1);

        foreach ($res as $k => $v) {
            $res[$k] = preg_replace(
                '/\\\([0-9a-f]{2})/ei',
                "chr(hexdec('\\1'))",
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
     * @param  string DN-component
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
     * Quote a string that should be used in a DN
     *
     * Special characters will be escaped
     *
     * @param  string DN-component
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
