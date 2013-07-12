<?php

/**
 * LDAP connection handler
 */
namespace Icinga\Protocol\Ldap;

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
     * @param array LDAP connection credentials
     */
    public function __construct($config)
    {
        $this->hostname = $config->hostname;
        $this->bind_dn  = $config->bind_dn;
        $this->bind_pw  = $config->bind_pw;
        $this->root_dn  = $config->root_dn;

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
        $results = @ldap_search(
            $this->ds,
            $this->root_dn,
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

    public function testCredentials($username, $password)
    {
        $ds = $this->prepareNewConnection();

        $r = @ldap_bind($ds, $username, $password);
        if ($r) {
            log::debug(
                'Successfully tested LDAP credentials (%s / %s)',
                $username,
                '***'
            );
            return true;
        } else {
            log::debug(
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
                    Log::debug('LDAP STARTTLS succeeded');
                } else {
                    Log::debug('LDAP STARTTLS failed: %s', ldap_error($ds));
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

            Log::warn('No LDAPv3 support detected');
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
                    'Capability query failed: %s',
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

        $result = $this->cleanupAttributes(
            ldap_get_attributes($ds, $entry)
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

    public function __destruct()
    {
        putenv('LDAPRC');
    }
}
