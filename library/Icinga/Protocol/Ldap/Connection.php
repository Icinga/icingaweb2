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

namespace Icinga\Protocol\Ldap;

use Icinga\Application\Platform;
use Icinga\Application\Config;
use Icinga\Logger\Logger;
use \Zend_Config;

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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Connection
{
    const LDAP_NO_SUCH_OBJECT = 0x20;

    protected $ds;
    protected $hostname;
    protected $port = 389;
    protected $bind_dn;
    protected $bind_pw;
    protected $root_dn;
    protected $count;
    protected $ldap_extension = array(
        '1.3.6.1.4.1.1466.20037' => 'STARTTLS',
        // '1.3.6.1.4.1.4203.1.11.1' => '11.1', // PASSWORD_MODIFY
        // '1.3.6.1.4.1.4203.1.11.3' => '11.3', // Whoami
        // '1.3.6.1.1.8' => '8', // Cancel Extended Request
    );

    protected $ms_capability = array(
        // Prefix LDAP_CAP_
        // Source: http://msdn.microsoft.com/en-us/library/cc223359.aspx

        // Running Active Directory as AD DS:
        '1.2.840.113556.1.4.800'  => 'ACTIVE_DIRECTORY_OID',

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

    protected $root;

    protected $supports_v3  = false;
    protected $supports_tls = false;

    /**
     * Constructor
     *
     * TODO: Allow to pass port and SSL options
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->hostname = $config->hostname;
        $this->bind_dn  = $config->bind_dn;
        $this->bind_pw  = $config->bind_pw;
        $this->root_dn  = $config->root_dn;
        $this->port = $config->get('port', $this->port);
    }

    public function getDN()
    {
        return $this->root_dn;
    }

    public function root()
    {
        if ($this->root === null) {
            $this->root = Root::forConnection($this);
        }
        return $this->root;
    }

    public function select()
    {
        return new Query($this);
    }

    public function fetchOne($query, $fields = array())
    {
        $row = (array) $this->fetchRow($query, $fields);
        return array_shift($row);
    }

    public function hasDN($dn)
    {
        $this->connect();
        $result = ldap_read($this->ds, $dn, '(objectClass=*)', array('objectClass'));
        return ldap_count_entries($this->ds, $result) > 0;
    }

    public function deleteRecursively($dn)
    {
        $this->connect();
        $result = @ldap_list($this->ds, $dn, '(objectClass=*)', array('objectClass'));
        if ($result === false) {
            if (ldap_errno($this->ds) === self::LDAP_NO_SUCH_OBJECT) {
                return false;
            }
            throw new \Exception(
                sprintf(
                    'LDAP list for "%s" failed: %s',
                    $dn,
                    ldap_error($this->ds)
                )
            );
        }
        $children = ldap_get_entries($this->ds, $result);
        for($i = 0; $i < $children['count']; $i++) {
            $result = $this->deleteRecursively($children[$i]['dn']);
            if (!$result) {
                //return result code, if delete fails
                throw new \Exception(sprintf('Recursively deleting "%s" failed', $dn));
            }
        }
        return $this->deleteDN($dn);
    }

    public function deleteDN($dn)
    {
        $this->connect();

        $result = @ldap_delete($this->ds, $dn);
        if ($result === false) {
            if (ldap_errno($this->ds) === self::LDAP_NO_SUCH_OBJECT) {
                return false;
            }
            throw new \Exception(
                sprintf(
                    'LDAP delete for "%s" failed: %s',
                    $dn,
                    ldap_error($this->ds)
                )
            );
        }

        return true;
    }

    public function fetchDN($query, $fields = array())
    {
        $rows = $this->fetchAll($query, $fields);
        if (count($rows) !== 1) {
            throw new \Exception(
                sprintf(
                    'Cannot fetch single DN for %s',
                    $query
                )
            );
        }
        return key($rows);
    }

    public function fetchRow($query, $fields = array())
    {
        // TODO: This is ugly, make it better!
        $results = $this->fetchAll($query, $fields);
        return array_shift($results);
    }

    public function count(Query $query)
    {
        $results = $this->runQuery($query, '+');
        if (! $results) {
            return 0;
        }
        return ldap_count_entries($this->ds, $results);
    }

    public function fetchAll($query, $fields = array())
    {
        $offset = null;
        $limit = null;
        if ($query->hasLimit()) {
            $offset = $query->getOffset();
            $limit  = $query->getLimit();
        }
        $entries = array();
        $results = $this->runQuery($query, $fields);
        if (! $results) {
            return array();
        }
        $entry = ldap_first_entry($this->ds, $results);
        $count = 0;
        while ($entry) {
            if (($offset === null || $offset <= $count)
                && ($limit === null || ($offset + $limit) >= $count)
            ) {
                $attrs = ldap_get_attributes($this->ds, $entry);
                $entries[ldap_get_dn($this->ds, $entry)]
                    = $this->cleanupAttributes($attrs);
            }
            $count++;
            $entry = ldap_next_entry($this->ds, $entry);
        }
        ldap_free_result($results);
        return $entries;
    }

    public function cleanupAttributes(& $attrs)
    {
        $clean = (object) array();
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
        $base = $query->hasBase() ? $query->getBase() : $this->root_dn;
        $results = @ldap_search(
            $this->ds,
            $base,
            (string) $query,
            $fields,
            0, // Attributes and values
            0  // No limit - at least where possible
        );
        if ($results === false) {
            if (ldap_errno($this->ds) === self::LDAP_NO_SUCH_OBJECT) {
                return false;
            }
            throw new \Exception(
                sprintf(
                    'LDAP query "%s" (root %s) failed: %s',
                    $query,
                    $this->root_dn,
                    ldap_error($this->ds)
                )
            );
        }
        $list = array();
        if ($query instanceof Query) {
            foreach ($query->getSortColumns() as $col) {
                ldap_sort($this->ds, $results, $col[0]);
            }
        }
        return $results;
    }

    public function testCredentials($username, $password)
    {
        $ds = $this->prepareNewConnection();

        $r = @ldap_bind($ds, $username, $password);
        if ($r) {
            Logger::debug(
                'Successfully tested LDAP credentials (%s / %s)',
                $username,
                '***'
            );
            return true;
        } else {
            Logger::debug(
                'Testing LDAP credentials (%s / %s) failed: %s',
                $username,
                '***',
                ldap_error($ds)
            );
            return false;
        }
    }

    protected function getConfigDir($sub = null)
    {
        $dir = Config::getInstance()->getConfigDir() . '/ldap';
        if ($sub !== null) {
            $dir .= '/' . $sub;
        }
        return $dir;
    }

    protected function discoverServerlistForDomain($domain)
    {
        $ldaps_records = dns_get_record('_ldaps._tcp.' . $domain, DNS_SRV);
        $ldap_records  = dns_get_record('_ldap._tcp.' . $domain, DNS_SRV);
    }

    protected function prepareNewConnection()
    {
        $use_tls = false;
        $force_tls = true;
        $force_tls = false;

        if ($use_tls) {
            $this->prepareTlsEnvironment();
        }

        $ds = ldap_connect($this->hostname, $this->port);
        $cap = $this->discoverCapabilities($ds);

        if ($use_tls) {
            if ($cap->starttls) {
                if (@ldap_start_tls($ds)) {
                    Logger::debug('LDAP STARTTLS succeeded');
                } else {
                    Logger::debug('LDAP STARTTLS failed: %s', ldap_error($ds));
                    throw new \Exception(
                        sprintf(
                            'LDAP STARTTLS failed: %s',
                            ldap_error($ds)
                        )
                    );
                }
            } elseif ($force_tls) {
                throw new \Exception(
                    sprintf(
                        'TLS is required but not announced by %s',
                        $this->host_name
                    )
                );
            } else {
                // TODO: Log noticy -> TLS enabled but not announced
            }
        }
        // ldap_rename requires LDAPv3:
        if ($cap->ldapv3) {
            if (! ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
                throw new Exception('LDAPv3 is required');
            }
        } else {

            // TODO: remove this -> FORCING v3 for now
            ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

            Logger::warning('No LDAPv3 support detected');
        }

        // Not setting this results in "Operations error" on AD when using the
        // whole domain as search base:
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
        // ldap_set_option($ds, LDAP_OPT_DEREF, LDAP_DEREF_NEVER);
        return $ds;
    }

    protected function prepareTlsEnvironment()
    {
        $strict_tls   = true;
        // TODO: allow variable known CA location (system VS Icinga)
        if (Platform::isWindows()) {
            // putenv('LDAP...')
        } else {
            if ($strict_tls) {
                $ldap_conf = $this->getConfigDir('ldap_ca.conf');
            } else {
                $ldap_conf = $this->getConfigDir('ldap_nocert.conf');
            }
            putenv('LDAPRC=' . $ldap_conf);
            if (getenv('LDAPRC') !== $ldap_conf) {
                throw new Exception('putenv failed');
            }
        }
    }

    protected function discoverCapabilities($ds)
    {
        $query = $this->select()->from(
            '*',
            array(
                'defaultNamingContext',
                'namingContexts',
                'vendorName',
                'vendorVersion',
                'supportedSaslMechanisms',
                'dnsHostName',
                'schemaNamingContext',
                'supportedLDAPVersion', // => array(3, 2)
                'supportedCapabilities',
                'supportedExtension',
                '+'
            )
        );
        $fields = $query->listFields();

        $result = @ldap_read(
            $ds,
            '',
            (string) $query,
            $query->listFields()
        );

        if (! $result) {
            throw new Exception(
                sprintf(
                    'Capability query failed (%s:%d): %s',
                    $this->hostname,
                    $this->port,
                    ldap_error($ds)
                )
            );
        }
        $entry = ldap_first_entry($ds, $result);

        $cap = (object) array(
            'ldapv3'   => false,
            'starttls' => false,
        );

        if ($entry === false) {
            // TODO: Is it OK to have no capabilities?
            return $cap;
        }
        $ldapAttributes = ldap_get_attributes($ds, $entry);
        $result = $this->cleanupAttributes(
            $ldapAttributes
        );
        /*
        if (isset($result->dnsHostName)) {
            ldap_set_option($ds, LDAP_OPT_HOST_NAME, $result->dnsHostName);
        }
        */

        if ((is_string($result->supportedLDAPVersion)
            && (int) $result->supportedLDAPVersion === 3)
            || (is_array($result->supportedLDAPVersion)
              && in_array(3, $result->supportedLDAPVersion)
        )) {
            $cap->ldapv3 = true;
        }

        if (isset($result->supportedCapabilities)) {
            foreach ($result->supportedCapabilities as $oid) {
                if (array_key_exists($oid, $this->ms_capability)) {
                    // echo $this->ms_capability[$oid] . "\n";
                }
            }
        }
        if (isset($result->supportedExtension)) {
            foreach ($result->supportedExtension as $oid) {
                if (array_key_exists($oid, $this->ldap_extension)) {
                    if ($this->ldap_extension[$oid] === 'STARTTLS') {
                        $cap->starttls = true;
                    }
                }
            }
        }
        return $cap;
    }

    public function connect()
    {
        if ($this->ds !== null) {
            return;
        }
        $this->ds = $this->prepareNewConnection();

        $r = @ldap_bind($this->ds, $this->bind_dn, $this->bind_pw);

        if (! $r) {
            throw new \Exception(
                sprintf(
                    'LDAP connection to %s:%s (%s / %s) failed: %s',
                    $this->hostname,
                    $this->port,
                    $this->bind_dn,
                    '***' /* $this->bind_pw */,
                    ldap_error($this->ds)
                )
            );
        }
    }

    /**
     * Create an ldap entry
     *
     * @param   string  $dn     DN to add
     * @param   array   $entry  Entry description
     *
     * @return bool             True on success
     */
    public function addEntry($dn, array $entry)
    {
        return ldap_add($this->ds, $dn, $entry);
    }

    /**
     * Modify a ldap entry
     *
     * @param string $dn    DN of the entry to change
     * @param array $entry  Change values
     *
     * @return bool         True on success
     */
    public function modifyEntry($dn, array $entry)
    {
        return ldap_modify($this->ds, $dn, $entry);
    }

    /**
     * Move entry to a new DN
     *
     * @param   string $dn          DN of the object
     * @param   string $newRdn      Relative DN identifier
     * @param   string $newParentDn Parent or superior entry
     * @throws  \Exception          Thrown then rename failed
     *
     * @return bool                 True on success
     */
    public function moveEntry($dn, $newRdn, $newParentDn)
    {
        $returnValue = ldap_rename($this->ds, $dn, $newRdn, $newParentDn, false);

        if ($returnValue === false) {
            throw new \Exception('Could not move entry: ' . ldap_error($this->ds));
        }

        return $returnValue;
    }

    public function __destruct()
    {
        putenv('LDAPRC');
    }
}
