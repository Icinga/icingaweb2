<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol;

/**
 * Discover dns records using regular or reverse lookup
 */
class Dns
{
    /**
     * Discover all service records on a given domain
     *
     * @param string    $domain     The domain to search
     * @param string    $service    The type of the service, like for example 'ldaps' or 'ldap'
     * @param string    $protocol   The transport protocol used by the service, defaults to 'tcp'
     *
     * @return array                An array of all found service records
     */
    public static function getSrvRecords($domain, $service, $protocol = 'tcp')
    {
        $records = dns_get_record('_' . $service . '._' . $protocol . '.' . $domain, DNS_SRV);
        return $records === false ? array() : $records;
    }

    /**
     * Get all ldap records for the given domain
     *
     * @param   string  $query    The domain to query
     * @param   int     $type     The type of DNS-entry to fetch, see
     *                            http://www.php.net/manual/de/function.dns-get-record.php for available types
     *
     * @return  array|null        An array of record entries
     */
    public static function records($query, $type = DNS_ANY)
    {
        return dns_get_record($query, $type);
    }

    /**
     * Reverse lookup all host names available on the given ip address
     *
     * @param   string  $ipAddress
     * @param   int     $type
     *
     * @return array|null
     */
    public static function ptr($ipAddress, $type = DNS_ANY)
    {
        $host = gethostbyaddr($ipAddress);
        if ($host === false || $host === $ipAddress) {
            // malformed input or no host found
            return null;
        }
        return self::records($host, $type);
    }

    /**
     * Get the IPv4 address of the given hostname.
     *
     * @param   $hostname       The hostname to resolve
     *
     * @return  string|null     The IPv4 address of the given hostname or null, when no entry exists.
     */
    public static function ipv4($hostname)
    {
        $records = dns_get_record($hostname, DNS_A);
        if ($records !== false && count($records) > 0) {
            return $records[0]['ip'];
        }
        return null;
    }

    /**
     * Get the IPv6 address of the given hostname.
     *
     * @param   $hostname       The hostname to resolve
     *
     * @return  string|null     The IPv6 address of the given hostname or null, when no entry exists.
     */
    public static function ipv6($hostname)
    {
        $records = dns_get_record($hostname, DNS_AAAA);
        if ($records !== false && count($records) > 0) {
            return $records[0]['ip'];
        }
        return null;
    }
}
