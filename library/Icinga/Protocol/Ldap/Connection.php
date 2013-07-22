<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Ldap;

use Icinga\Exception\ConfigurationError as ConfigError;
use Icinga\Application\Platform;
use Icinga\Application\Config;
use Icinga\Application\Logger as Log;

/**
 * Backend class managing all the LDAP stuff for you.
 *
 * Usage example:
 *
 * <code>
 * $lconf = new Connection((object) array(
 *     'hostname' => 'localhost',
 *     'root_dn'  => 'dc=monitoring,dc=...',
 *     'bind_dn'  => 'cn=Mangager,dc=monitoring,dc=...',
 *     'bind_pw'  => '***'
 * ));
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Connection
{
    /**
     * @var string
     */
    protected $ds;

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var string
     */
    protected $bind_dn;

    /**
     * @var string
     */
    protected $bind_pw;

    /**
     * @var string
     */
    protected $root_dn;

    /**
     * @var string
     */
    protected $count;

    /**
     * @var array
     */
    protected $ldap_extension = array(
        '1.3.6.1.4.1.1466.20037' => 'STARTTLS', // notes?
        // '1.3.6.1.4.1.4203.1.11.1' => '11.1', // PASSWORD_MODIFY
        // '1.3.6.1.4.1.4203.1.11.3' => '11.3', // Whoami
        // '1.3.6.1.1.8' => '8', // Cancel Extended Request
    );

    protected $use_tls = false;
    protected $force_tls = false;


    /**
     * @var array
     */
    protected $ms_capability = array(
        // Prefix LDAP_CAP_
        // Source: http://msdn.microsoft.com/en-us/library/cc223359.aspx

        // Running Active Directory as AD DS:
        '1.2.840.113556.1.4.800' => 'ACTIVE_DIRECTORY_OID',
        // Capable of signing and sealing on an NTLM authenticated connection
        // and of performing subsequent binds on a signed or sealed connection.
        '1.2.840.113556.1.4.1791' => 'ACTIVE_DIRECTORY_LDAP_INTEG_OID',
        // If AD DS: running at least W2K3, if AD LDS running at least W2K8
        '1.2.840.113556.1.4.1670' => 'ACTIVE_DIRECTORY_V51_OID',
        // If AD LDS: accepts DIGEST-MD5 binds for AD LDSsecurity principals
        '1.2.840.113556.1.4.1880' => 'ACTIVE_DIRECTORY_ADAM_DIGEST',
        // Running Active Directory as AD LDS
        '1.2.840.113556.1.4.1851' => 'ACTIVE_DIRECTORY_ADAM_OID',
        // If AD DS: it's a Read Only DC (RODC)
        '1.2.840.113556.1.4.1920' => 'ACTIVE_DIRECTORY_PARTIAL_SECRETS_OID',
        // Running at least W2K8
        '1.2.840.113556.1.4.1935' => 'ACTIVE_DIRECTORY_V60_OID',
        // Running at least W2K8r2
        '1.2.840.113556.1.4.2080' => 'ACTIVE_DIRECTORY_V61_R2_OID',
        // Running at least W2K12
        '1.2.840.113556.1.4.2237' => 'ACTIVE_DIRECTORY_W8_OID',

    );

    /**
     * @var string
     */
    protected $root;

    /**
     * Constructor
     *
     * TODO: Allow to pass port and SSL options
     *
     * @param array LDAP connection credentials
     */
    public function __construct($config)
    {
        $this->hostname = $config->hostname;
        $this->bind_dn = $config->bind_dn;
        $this->bind_pw = $config->bind_pw;
        $this->root_dn = $config->root_dn;
        $this->use_tls = isset($config->tls) ? $config->tls : false;
        $this->force_tls = $this->use_tls;
    }

    /**
     * @return string
     */
    public function getDN()
    {
        return $this->root_dn;
    }

    /**
     * @return Root|string
     */
    public function root()
    {
        if ($this->root === null) {
            $this->root = Root::forConnection($this);
        }
        return $this->root;
    }

    /**
     * @return Query
     */
    public function select()
    {
        return new Query($this);
    }

    /**
     * @param $query
     * @param array $fields
     * @return mixed
     */
    public function fetchOne($query, $fields = array())
    {
        $row = (array)$this->fetchRow($query, $fields);
        return array_shift($row);
    }

    /**
     * @param $query
     * @param array $fields
     * @return mixed
     * @throws Exception
     */
    public function fetchDN($query, $fields = array())
    {
        $rows = $this->fetchAll($query, $fields);
        if (count($rows) !== 1) {
            throw new Exception(
                sprintf(
                    'Cannot fetch single DN for %s',
                    $query
                )
            );
        }
        return key($rows);
    }

    /**
     * @param $query
     * @param array $fields
     * @return mixed
     */
    public function fetchRow($query, $fields = array())
    {
        // TODO: This is ugly, make it better!
        $results = $this->fetchAll($query, $fields);
        return array_shift($results);
    }

    /**
     * @param Query $query
     * @return int
     */
    public function count(Query $query)
    {
        $results = $this->runQuery($query, '+');
        return ldap_count_entries($this->ds, $results);
    }

    /**
     * @param $query
     * @param array $fields
     * @return array
     */
    public function fetchAll($query, $fields = array())
    {
        $offset = null;
        $limit = null;
        if ($query->hasLimit()) {
            $offset = $query->getOffset();
            $limit = $query->getLimit();
        }
        $entries = array();
        $results = $this->runQuery($query, $fields);
        $entry = ldap_first_entry($this->ds, $results);
        $count = 0;
        while ($entry) {
            if (($offset === null || $offset <= $count)
                && ($limit === null || ($offset + $limit) >= $count)
            ) {
                $attrs = ldap_get_attributes($this->ds, $entry);
                $entries[ldap_get_dn($this->ds, $entry)] = $this->cleanupAttributes($attrs);
            }
            $count++;
            $entry = ldap_next_entry($this->ds, $entry);
        }
        ldap_free_result($results);
        return $entries;
    }

    /**
     * @param $attrs
     * @return object
     */
    public function cleanupAttributes(& $attrs)
    {
        $clean = (object)array();
        for ($i = 0; $i < $attrs['count']; $i++) {
            $attr_name = $attrs[$i];
            if ($attrs[$attr_name]['count'] === 1) {
                $clean->$attr_name = $attrs[$attr_name][0];
            } else {
                for ($j = 0; $j < $attrs[$attr_name]['count']; $j++) {
                    $clean->{$attr_name}[] = $attrs[$attr_name][$j];
                }
            }
        }
        return $clean;
    }

    /**
     * @param $query
     * @param $fields
     * @return resource
     * @throws Exception
     */
    protected function runQuery($query, $fields)
    {
        $this->connect();
        if ($query instanceof Query) {
            $fields = $query->listFields();
        }
        // WARNING:
        // We do not support pagination right now, and there is no chance to
        // do so for PHP < 5.4. Warnings about "Sizelimit exceeded" will
        // therefore not be hidden right now.
        Log::debug("Query: %s", $query->__toString());
        $results = ldap_search(
            $this->ds,
            $this->root_dn,
            (string)$query,
            $fields,
            0, // Attributes and values
            0 // No limit - at least where possible
        );
        if (!$results) {
            throw new Exception(
                sprintf(
                    'LDAP query "%s" (root %s) failed: %s',
                    $query,
                    $this->root_dn,
                    ldap_error($this->ds)
                )
            );

            die('Query failed');
        }
        $list = array();
        if ($query instanceof Query) {
            foreach ($query->getSortColumns() as $col) {
                ldap_sort($this->ds, $results, $col[0]);
            }
        }
        return $results;
    }

    /**
     * @param $username
     * @param $password
     * @return bool
     */
    public function testCredentials($username, $password)
    {
        Log::debug("Trying to connect to %s", $this->hostname);
        $ds = ldap_connect($this->hostname);
        Log::debug("ldap_bind (%s)", $username);
        $r = @ldap_bind($ds, $username, $password);
        if ($r) {
            return true;
        } else {
            log::fatal(
                'LDAP connection (%s / %s) failed: %s',
                $username,
                '***',
                ldap_error($ds)
            );
            return false;
            /* TODO: Log failure
            throw new Exception(sprintf(
                'LDAP connection (%s / %s) failed: %s',
                $username,
                '***',
                ldap_error($ds)
            ));
            */
        }
    }

    /**
     * @return string
     */
    protected function getConfigDir()
    {
        return Config::getInstance()->getConfigDir() . '/ldap';
    }

    /**
     * @param $domain
     */
    protected function discoverServerlistForDomain($domain)
    {
        $ldaps_records = dns_get_record('_ldaps._tcp.' . $domain, DNS_SRV);
        $ldap_records = dns_get_record('_ldap._tcp.' . $domain, DNS_SRV);
    }

    /**
     *
     */
    protected function prepareTlsEnvironment()
    {
        $strict_tls = true;
        $use_local_ca = true;
        if (Platform::isWindows()) {
        } else {
            $cfg_dir = $this->getConfigDir();
            if ($strict_tls) {
                putenv(sprintf('LDAPRC=%s/%s', $cfg_dir, 'ldap_ca.conf'));
            } else {
                putenv(sprintf('LDAPRC=%s/%s', $cfg_dir, 'ldap_nocert.conf'));
            }
        }
        // file_put_contents('/tmp/tom_LDAP.conf', "TLS_REQCERT never\n");
    }

    /**
     * @return object
     */
    protected function fetchRootDseDetails()
    {
        $query = $this->select()->from('*', array('+'));

        /*,  array(
            'defaultNamingContext',
            'namingContexts',
            'supportedSaslMechanisms',
            'dnsHostName',
            'schemaNamingContext',
            'supportedLDAPVersion', // => array(3, 2)
            'supportedCapabilities'
        ))*/

        $fields = $query->listFields();

        $result = ldap_read(
            $this->ds,
            '',
            (string)$query,
            $query->listFields(),
            0,
            0
        );

        $entry = ldap_first_entry($this->ds, $result);
        $result = $this->cleanupAttributes(ldap_get_attributes($this->ds, $entry));


        if (isset($result->supportedCapabilities)) {
            foreach ($result->supportedCapabilities as $oid) {
                if (array_key_exists($oid, $this->ms_capability)) {
                    echo $this->ms_capability[$oid] . "\n";
                }
            }
        }
        if (isset($result->supportedExtension)) {
            foreach ($result->supportedExtension as $oid) {
                if (array_key_exists($oid, $this->ldap_extension)) {
                    // STARTTLS -> läuft mit OpenLDAP
                }
            }
        }
        return $result;
    }

    /**
     *
     */
    public function discoverCapabilities()
    {
        $this->fetchRootDseDetails();
    }

    /**
     * @throws Exception
     */
    public function connect()
    {
        if ($this->ds !== null) {
            return;
        }

        if ($this->use_tls) {
            $this->prepareTlsEnvironment();
        }
        Log::debug("Trying to connect to %s", $this->hostname);
        $this->ds = ldap_connect($this->hostname, 389);
        $this->discoverCapabilities();
        if ($this->use_tls) {
            Log::debug("Trying ldap_start_tls()");
            if (@ldap_start_tls($this->ds)) {
                Log::debug("Trying ldap_start_tls() succeeded");
            } else {
                Log::warn(
                    "ldap_start_tls() failed: %s. Does your ldap_ca.conf point to the certificate? ",
                    ldap_error($this->ds)
                );
            }
        }


        // ldap_rename requires LDAPv3:
        if (!ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            throw new Exception('LDAPv3 is required');
        }

        // Not setting this results in "Operations error" on AD when using the
        // whole domain as search base:
        ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0);

        // ldap_set_option($this->ds, LDAP_OPT_DEREF, LDAP_DEREF_NEVER);
        Log::debug("Trying ldap_bind(%s)", $this->bind_dn);

        $r = @ldap_bind($this->ds, $this->bind_dn, $this->bind_pw);

        if (!$r) {
            log::fatal(
                'LDAP connection (%s / %s) failed: %s',
                $this->bind_dn,
                '***',
                ldap_error($this->ds)
            );
            throw new ConfigError(
                sprintf(
                    'Could not connect to the authentication server, please '.
                    'review your LDAP connection settings.'
                )
            );
        }
    }
}
