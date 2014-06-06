<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol;

/**
 * Discover dns records using regular or reverse lookup
 */
class Dns {

    /**
     * Get all ldap records for the given domain
     *
     * @param String $query     The domain to query
     *
     * @return array        An array of entries
     */
    public static function ldapRecords($query)
    {
        $ldaps_records = dns_get_record('_ldaps._tcp.' . $query);
        $ldap_records  = dns_get_record('_ldap._tcp.' . $query);
        return array_merge($ldaps_records, $ldap_records);
    }

    /**
     * Get all ldap records for the given domain
     *
     * @param String $query    The domain to query
     * @param int    $type     The type of DNS-entry to fetch, see http://www.php.net/manual/de/function.dns-get-record.php
     *                          for available types
     *
     * @return array|Boolean       An array of entries
     */
    public static function records($query, $type = DNS_ANY)
    {
        return dns_get_record($query, $type);
    }

    /**
     * Reverse lookup all hostname on the given ip address
     *
     * @param     $ipAddress
     * @param int $type
     *
     * @return array|Boolean
     */
    public static function ptr($ipAddress, $type = DNS_ANY)
    {
        $host = gethostbyaddr($ipAddress);
        if ($host === false || $host === $ipAddress) {
            // malformed input or no host found
            return false;
        }
        return self::records($host, $type);
    }

    /**
     * Get the IPv4 address of the given hostname.
     *
     * @param $hostname The hostname to resolve
     *
     * @return String|Boolean   The IPv4 address of the given hostname, or false when no entry exists.
     */
    public static function ipv4($hostname)
    {
        $records = dns_get_record($hostname, DNS_A);
        if ($records !== false && sizeof($records) > 0) {
            return $records[0]['ip'];
        }
        return false;
    }

    /**
     * Get the IPv6 address of the given hostname.
     *
     * @param $hostname The hostname to resolve
     *
     * @return String|Boolean   The IPv6 address of the given hostname, or false when no entry exists.
     */
    public static function ipv6($hostname)
    {
        $records = dns_get_record($hostname, DNS_AAAA);
        if ($records !== false && sizeof($records) > 0) {
            return $records[0]['ip'];
        }
        return false;
    }
} 