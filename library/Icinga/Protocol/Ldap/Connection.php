<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use Exception;
use Icinga\Protocol\Ldap\Exception as LdapException;
use Icinga\Application\Platform;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;

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
    const LDAP_NO_SUCH_OBJECT = 32;
    const LDAP_SIZELIMIT_EXCEEDED = 4;
    const LDAP_ADMINLIMIT_EXCEEDED = 11;
    const PAGE_SIZE = 1000;

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

    /**
     * Whether the bind on this connection was already performed
     *
     * @var bool
     */
    protected $bound = false;

    protected $root;

    protected $supports_v3  = false;
    protected $supports_tls = false;

    protected $capabilities;
    protected $namingContexts;
    protected $discoverySuccess = false;

    /**
     * Constructor
     *
     * TODO: Allow to pass port and SSL options
     *
     * @param ConfigObject $config
     */
    public function __construct(ConfigObject $config)
    {
        $this->hostname = $config->hostname;
        $this->bind_dn  = $config->bind_dn;
        $this->bind_pw  = $config->bind_pw;
        $this->root_dn  = $config->root_dn;
        $this->port = $config->get('port', $this->port);
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function getPort()
    {
        return $this->port;
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
        $this->bind();

        $result = ldap_read($this->ds, $dn, '(objectClass=*)', array('objectClass'));
        return ldap_count_entries($this->ds, $result) > 0;
    }

    public function deleteRecursively($dn)
    {
        $this->connect();
        $this->bind();

        $result = @ldap_list($this->ds, $dn, '(objectClass=*)', array('objectClass'));
        if ($result === false) {
            if (ldap_errno($this->ds) === self::LDAP_NO_SUCH_OBJECT) {
                return false;
            }
            throw new LdapException(
                'LDAP list for "%s" failed: %s',
                $dn,
                ldap_error($this->ds)
            );
        }
        $children = ldap_get_entries($this->ds, $result);
        for ($i = 0; $i < $children['count']; $i++) {
            $result = $this->deleteRecursively($children[$i]['dn']);
            if (!$result) {
                //return result code, if delete fails
                throw new LdapException('Recursively deleting "%s" failed', $dn);
            }
        }
        return $this->deleteDN($dn);
    }

    public function deleteDN($dn)
    {
        $this->connect();
        $this->bind();

        $result = @ldap_delete($this->ds, $dn);
        if ($result === false) {
            if (ldap_errno($this->ds) === self::LDAP_NO_SUCH_OBJECT) {
                return false;
            }
            throw new LdapException(
                'LDAP delete for "%s" failed: %s',
                $dn,
                ldap_error($this->ds)
            );
        }

        return true;
    }

    /**
     * Fetch the distinguished name of the first result of the given query
     *
     * @param Query $query   The query returning the result set
     * @param array $fields  The fields to fetch
     *
     * @return string        Returns the distinguished name, or false when the given query yields no results
     * @throws LdapException When the query result is empty and contains no DN to fetch
     */
    public function fetchDN(Query $query, $fields = array())
    {
        $rows = $this->fetchAll($query, $fields);
        if (count($rows) !== 1) {
            throw new LdapException(
                'Cannot fetch single DN for %s',
                $query->create()
            );
        }
        return key($rows);
    }

    /**
     * @param       $query
     * @param array $fields
     *
     * @return mixed
     */
    public function fetchRow($query, $fields = array())
    {
        $query = clone $query;
        $query->limit(1);
        $query->setUsePagedResults(false);
        $results = $this->fetchAll($query, $fields);
        return array_shift($results);
    }

    /**
     * @param Query $query
     *
     * @return int
     */
    public function count(Query $query)
    {
        $this->connect();
        $this->bind();

        $count = 0;
        $results = $this->runQuery($query);
        while (! empty($results)) {
            $count += ldap_count_entries($this->ds, $results);
            $results = $this->runQuery($query);
        }

        return $count;
    }

    public function fetchAll(Query $query, $fields = array())
    {
        $this->connect();
        $this->bind();

        if ($query->getUsePagedResults() && version_compare(PHP_VERSION, '5.4.0') >= 0) {
            return $this->runPagedQuery($query, $fields);
        } else {
            return $this->runQuery($query, $fields);
        }
    }

    protected function runQuery(Query $query, $fields = array())
    {
        $limit = $query->getLimit();
        $offset = $query->hasOffset() ? $query->getOffset() - 1 : 0;

        $results = @ldap_search(
            $this->ds,
            $query->hasBase() ? $query->getBase() : $this->root_dn,
            $query->create(),
            empty($fields) ? $query->listFields() : $fields,
            0, // Attributes and values
            $limit ? $offset + $limit : 0
        );
        if ($results === false) {
            if (ldap_errno($this->ds) === self::LDAP_NO_SUCH_OBJECT) {
                return array();
            }

            throw new LdapException(
                'LDAP query "%s" (base %s) failed. Error: %s',
                $query->create(),
                $query->hasBase() ? $query->getBase() : $this->root_dn,
                ldap_error($this->ds)
            );
        } elseif (ldap_count_entries($this->ds, $results) === 0) {
            return array();
        }

        foreach ($query->getSortColumns() as $col) {
            ldap_sort($this->ds, $results, $col[0]);
        }

        $count = 0;
        $entries = array();
        $entry = ldap_first_entry($this->ds, $results);
        do {
            $count += 1;
            if ($offset === 0 || $offset < $count) {
                $entries[ldap_get_dn($this->ds, $entry)] = $this->cleanupAttributes(
                    ldap_get_attributes($this->ds, $entry)
                );
            }
        } while (($limit === 0 || $limit !== count($entries)) && ($entry = ldap_next_entry($this->ds, $entry)));

        ldap_free_result($results);
        return $entries;
    }

    protected function runPagedQuery(Query $query, $fields = array())
    {
        $limit = $query->getLimit();
        $offset = $query->hasOffset() ? $query->getOffset() - 1 : 0;
        $queryString = $query->create();
        $base = $query->hasBase() ? $query->getBase() : $this->root_dn;

        if (empty($fields)) {
            $fields = $query->listFields();
        }

        $count = 0;
        $cookie = '';
        $entries = array();
        do {
            ldap_control_paged_result($this->ds, static::PAGE_SIZE, true, $cookie);
            $results = @ldap_search($this->ds, $base, $queryString, $fields, 0, $limit ? $offset + $limit : 0);
            if ($results === false) {
                if (ldap_errno($this->ds) === self::LDAP_NO_SUCH_OBJECT) {
                    break;
                }

                throw new LdapException(
                    'LDAP query "%s" (base %s) failed. Error: %s',
                    $queryString,
                    $base,
                    ldap_error($this->ds)
                );
            } elseif (ldap_count_entries($this->ds, $results) === 0) {
                if (in_array(
                    ldap_errno($this->ds),
                    array(static::LDAP_SIZELIMIT_EXCEEDED, static::LDAP_ADMINLIMIT_EXCEEDED)
                )) {
                    Logger::warning(
                        'Unable to request more than %u results. Does the server allow paged search requests? (%s)',
                        $count,
                        ldap_error($this->ds)
                    );
                }

                break;
            }

            $entry = ldap_first_entry($this->ds, $results);
            do {
                $count += 1;
                if ($offset === 0 || $offset < $count) {
                    $entries[ldap_get_dn($this->ds, $entry)] = $this->cleanupAttributes(
                        ldap_get_attributes($this->ds, $entry)
                    );
                }
            } while (($limit === 0 || $limit !== count($entries)) && ($entry = ldap_next_entry($this->ds, $entry)));

            try {
                ldap_control_paged_result_response($this->ds, $results, $cookie);
            } catch (Exception $e) {
                // If the page size is greater than or equal to the sizeLimit value, the server should ignore the
                // control as the request can be satisfied in a single page: https://www.ietf.org/rfc/rfc2696.txt
                // This applies no matter whether paged search requests are permitted or not. You're done once you
                // got everything you were out for.
                if (count($entries) !== $limit) {
                    Logger::warning(
                        'Unable to request paged LDAP results. Does the server allow paged search requests? (%s)',
                        $e->getMessage()
                    );
                }
            }

            ldap_free_result($results);
        } while ($cookie && ($limit === 0 || count($entries) < $limit));

        if ($cookie) {
            // A sequence of paged search requests is abandoned by the client sending a search request containing a
            // pagedResultsControl with the size set to zero (0) and the cookie set to the last cookie returned by
            // the server: https://www.ietf.org/rfc/rfc2696.txt
            ldap_control_paged_result($this->ds, 0, false, $cookie);
            ldap_search($this->ds, $base, $queryString, $fields); // Returns no entries, due to the page size
        } else {
            // Reset the paged search request so that subsequent requests succeed
            ldap_control_paged_result($this->ds, 0);
        }

        return $entries; // TODO(7693): Sort entries post-processed
    }

    protected function cleanupAttributes($attrs)
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

    public function testCredentials($username, $password)
    {
        $this->connect();

        $r = @ldap_bind($this->ds, $username, $password);
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
                ldap_error($this->ds)
            );
            return false;
        }
    }

    /**
     * @param null $sub
     *
     * @return string
     */
    protected function getConfigDir($sub = null)
    {
        $dir = Config::$configDir . '/ldap';
        if ($sub !== null) {
            $dir .= '/' . $sub;
        }
        return $dir;
    }

    /**
     * Connect to the given ldap server and apply settings depending on the discovered capabilities
     *
     * @return resource        A positive LDAP link identifier
     * @throws LdapException   When the connection is not possible
     */
    protected function prepareNewConnection()
    {
        $use_tls = false;
        $force_tls = true;
        $force_tls = false;

        if ($use_tls) {
            $this->prepareTlsEnvironment();
        }

        $ds = ldap_connect($this->hostname, $this->port);
        try {
            $capabilities = $this->discoverCapabilities($ds);
            list($cap, $namingContexts) = $capabilities;
            $this->discoverySuccess = true;
        } catch (LdapException $e) {

            // discovery failed, guess defaults
            $cap = (object) array(
                'supports_ldapv3'   => true,
                'supports_starttls' => false,
                'msCapabilities'    => array()
            );
            $namingContexts = null;
        }
        $this->capabilities = $cap;
        $this->namingContexts = $namingContexts;

        if ($use_tls) {
            if ($cap->supports_starttls) {
                if (@ldap_start_tls($ds)) {
                    Logger::debug('LDAP STARTTLS succeeded');
                } else {
                    Logger::debug('LDAP STARTTLS failed: %s', ldap_error($ds));
                    throw new LdapException(
                        'LDAP STARTTLS failed: %s',
                        ldap_error($ds)
                    );
                }
            } elseif ($force_tls) {
                throw new LdapException(
                    'TLS is required but not announced by %s',
                    $this->hostname
                );
            } else {
                // TODO: Log noticy -> TLS enabled but not announced
            }
        }
        // ldap_rename requires LDAPv3:
        if ($cap->supports_ldapv3) {
            if (! ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
                throw new LdapException('LDAPv3 is required');
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
                throw new LdapException('putenv failed');
            }
        }
    }

    /**
     * Return if the capability object contains support for StartTLS
     *
     * @param $cap  The object containing the capabilities
     *
     * @return bool Whether StartTLS is supported
     */
    protected function hasCapabilityStartTLS($cap)
    {
        $cap = $this->getExtensionCapabilities($cap);
        return isset($cap['1.3.6.1.4.1.1466.20037']);
    }

    /**
     * Return if the capability objects contains support for LdapV3
     *
     * @param $cap
     *
     * @return bool
     */
    protected function hasCapabilityLdapV3($cap)
    {
        if ((is_string($cap->supportedLDAPVersion)
                && (int) $cap->supportedLDAPVersion === 3)
            || (is_array($cap->supportedLDAPVersion)
                && in_array(3, $cap->supportedLDAPVersion)
            )) {
            return true;
        }
        return false;
    }

    /**
     * Extract an array of all extension capabilities from the given ldap response
     *
     * @param $cap      object  The response returned by a ldap_search discovery query
     *
     * @return object           The extracted capabilities.
     */
    protected function getExtensionCapabilities($cap)
    {
        $extensions = array();
        if (isset($cap->supportedExtension)) {
            foreach ($cap->supportedExtension as $oid) {
                if (array_key_exists($oid, $this->ldap_extension)) {
                    if ($this->ldap_extension[$oid] === 'STARTTLS') {
                        $extensions['1.3.6.1.4.1.1466.20037'] = $this->ldap_extension['1.3.6.1.4.1.1466.20037'];
                    }
                }
            }
        }
        return $extensions;
    }

    /**
     * Extract an array of all MSAD capabilities from the given ldap response
     *
     * @param $cap      object  The response returned by a ldap_search discovery query
     *
     * @return object           The extracted capabilities.
     */
    protected function getMsCapabilities($cap)
    {
        $ms = array();
        foreach ($this->ms_capability as $name) {
            $ms[$this->convName($name)] = false;
        }

        if (isset($cap->supportedCapabilities)) {
            foreach ($cap->supportedCapabilities as $oid) {
                if (array_key_exists($oid, $this->ms_capability)) {
                    $ms[$this->convName($this->ms_capability[$oid])] = true;
                }
            }
        }
        return (object)$ms;
    }

    /**
     * Convert a single capability name entry into camel-case
     *
     * @param   $name   string  The name to convert
     *
     * @return          string  The name in camel-case
     */
    private function convName($name)
    {
        $parts = explode('_', $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = ucfirst(strtolower($part));
        }
        return implode('', $parts);
    }

    /**
     * Get the capabilities of this ldap server
     *
     * @return stdClass     An object, providing the flags 'ldapv3' and 'starttls' to indicate LdapV3 and StartTLS
     * support and an additional property 'msCapabilities', containing all supported active directory capabilities.
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * Get the default naming context of this ldap connection
     *
     * @return string|null the default naming context, or null when no contexts are available
     */
    public function getDefaultNamingContext()
    {
        $cap = $this->capabilities;
        if (isset($cap->defaultNamingContext)) {
            return $cap->defaultNamingContext;
        }
        $namingContexts = $this->namingContexts($cap);
        return empty($namingContexts) ? null : $namingContexts[0];
    }

    /**
     * Fetch the namingContexts for this Ldap-Connection
     *
     * @return array    the available naming contexts
     */
    public function namingContexts()
    {
        if (!isset($this->namingContexts)) {
            return array();
        }
        if (!is_array($this->namingContexts)) {
            return array($this->namingContexts);
        }
        return $this->namingContexts;
    }

    /**
     * Whether service discovery was successful
     *
     * @return boolean  True when ldap settings were discovered, false when
     *                   settings were guessed
     */
    public function discoverySuccessful()
    {
        return $this->discoverySuccess;
    }

    /**
     * Discover the capabilities of the given ldap-server
     *
     * @param  resource     $ds     The link identifier of the current ldap connection
     *
     * @return array                The capabilities and naming-contexts
     * @throws LdapException        When the capability query fails
     */
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
        $result = @ldap_read(
            $ds,
            '',
            $query->create(),
            $query->listFields()
        );

        if (! $result) {
            throw new LdapException(
                'Capability query failed (%s:%d): %s. Check if hostname and port of the'
                . ' ldap resource are correct and if anonymous access is permitted.',
                $this->hostname,
                $this->port,
                ldap_error($ds)
            );
        }
        $entry = ldap_first_entry($ds, $result);
        if ($entry === false) {
            throw new LdapException(
                'Capabilities not available (%s:%d): %s. Discovery of root DSE probably not permitted.',
                $this->hostname,
                $this->port,
                ldap_error($ds)
            );
        }

        $cap = (object) array(
            'supports_ldapv3'   => false,
            'supports_starttls' => false,
            'msCapabilities' => array()
        );

        $ldapAttributes = ldap_get_attributes($ds, $entry);
        $result = $this->cleanupAttributes($ldapAttributes);
        $cap->supports_ldapv3 = $this->hasCapabilityLdapV3($result);
        $cap->supports_starttls = $this->hasCapabilityStartTLS($result);
        $cap->msCapabilities = $this->getMsCapabilities($result);

        return array($cap,  $result->namingContexts);
    }

    /**
     * Try to connect to the given ldap server
     *
     * @throws LdapException   When connecting is not possible
     */
    public function connect()
    {
        if ($this->ds !== null) {
            return;
        }
        $this->ds = $this->prepareNewConnection();
    }

    /**
     * Try to bind to the current ldap domain using the provided bind_dn and bind_pw
     *
     * @throws LdapException   When binding is not possible
     */
    public function bind()
    {
        if ($this->bound) {
            return;
        }

        $r = @ldap_bind($this->ds, $this->bind_dn, $this->bind_pw);
        if (! $r) {
            throw new LdapException(
                'LDAP connection to %s:%s (%s / %s) failed: %s',
                $this->hostname,
                $this->port,
                $this->bind_dn,
                '***' /* $this->bind_pw */,
                ldap_error($this->ds)
            );
        }
        $this->bound = true;
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
     * @param string $dn        DN of the entry to change
     * @param array  $entry     Change values
     *
     * @return bool             True on success
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
     * @throws  LdapException       Thrown then rename failed
     *
     * @return bool                 True on success
     */
    public function moveEntry($dn, $newRdn, $newParentDn)
    {
        $returnValue = ldap_rename($this->ds, $dn, $newRdn, $newParentDn, false);

        if ($returnValue === false) {
            throw new LdapException('Could not move entry: ' . ldap_error($this->ds));
        }

        return $returnValue;
    }

    public function __destruct()
    {
        putenv('LDAPRC');
    }
}
