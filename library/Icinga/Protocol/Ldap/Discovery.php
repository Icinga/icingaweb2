<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use Icinga\Data\ConfigObject;
use Icinga\Protocol\Dns;

class Discovery
{
    /**
     * @var LdapConnection
     */
    private $connection;

    /**
     * @param   LdapConnection  $conn   The ldap connection to use for the discovery
     */
    public function __construct(LdapConnection $conn)
    {
        $this->connection = $conn;
    }

    /**
     * Suggests a resource configuration of hostname, port and root_dn
     * based on the discovery
     *
     * @return array    The suggested configuration as an array
     */
    public function suggestResourceSettings()
    {
        return array(
            'hostname' => $this->connection->getHostname(),
            'port' => $this->connection->getPort(),
            'root_dn' => $this->connection->getCapabilities()->getDefaultNamingContext()
        );
    }

    /**
     * Suggests a backend configuration of base_dn, user_class and user_name_attribute
     * based on the discovery
     *
     * @return array    The suggested configuration as an array
     */
    public function suggestBackendSettings()
    {
        if ($this->isAd()) {
            return array(
                'backend' => 'msldap',
                'base_dn' => $this->connection->getCapabilities()->getDefaultNamingContext(),
                'user_class' => 'user',
                'user_name_attribute' => 'sAMAccountName'
            );
        } else {
            return array(
                'backend' => 'ldap',
                'base_dn' => $this->connection->getCapabilities()->getDefaultNamingContext(),
                'user_class' => 'inetOrgPerson',
                'user_name_attribute' => 'uid'
            );
        }
    }

    /**
     * Whether the suggested ldap server is an ActiveDirectory
     *
     * @return boolean
     */
    public function isAd()
    {
        return $this->connection->getCapabilities()->isActiveDirectory();
    }

    /**
     * Whether the discovery was successful
     *
     * @return bool     False when the suggestions are guessed
     */
    public function isSuccess()
    {
        return $this->connection->discoverySuccessful();
    }

    /**
     * Why the discovery failed
     *
     * @return \Exception|null
     */
    public function getError()
    {
        return $this->connection->getDiscoveryError();
    }

    /**
     * Discover LDAP servers on the given domain
     *
     * @param  string   $domain The object containing the form elements
     *
     * @return Discovery        True when the discovery was successful, false when the configuration was guessed
     */
    public static function discoverDomain($domain)
    {
        if (! isset($domain)) {
            return false;
        }

        // Attempt 1: Connect to the domain directly
        $disc = Discovery::discover($domain, 389);
        if ($disc->isSuccess()) {
            return $disc;
        }

        // Attempt 2: Discover all available ldap dns records and connect to the first one
        $records = array_merge(Dns::getSrvRecords($domain, 'ldap'), Dns::getSrvRecords($domain, 'ldaps'));
        if (isset($records[0])) {
            $record = $records[0];
            return Discovery::discover(
                isset($record['target']) ? $record['target'] : $domain,
                isset($record['port'])   ? $record['port'] : $domain
            );
        }

        // Return the first failed discovery, which will suggest properties based on guesses
        return $disc;
    }

    /**
     * Convenience method to instantiate a new Discovery
     *
     * @param $host         The host on which to execute the discovery
     * @param $port         The port on which to execute the discovery
     *
     * @return Discover     The resulting Discovery
     */
    public static function discover($host, $port)
    {
        $conn = new LdapConnection(new ConfigObject(array(
            'hostname' => $host,
            'port'     => $port
        )));
        return new Discovery($conn);
    }
}
