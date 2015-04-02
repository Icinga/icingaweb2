<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use Icinga\Exception\ProgrammingError;
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
 */
class Connection
{
    const LDAP_NO_SUCH_OBJECT = 32;
    const LDAP_SIZELIMIT_EXCEEDED = 4;
    const LDAP_ADMINLIMIT_EXCEEDED = 11;
    const PAGE_SIZE = 1000;

    /**
     * Encrypt connection using STARTTLS (upgrading a plain text connection)
     *
     * @var string
     */
    const STARTTLS = 'starttls';

    /**
     * Encrypt connection using LDAP over SSL (using a separate port)
     *
     * @var string
     */
    const LDAPS = 'ldaps';

    /**
     * Encryption for the connection if any
     *
     * @var string|null
     */
    protected $encryption;

    protected $ds;
    protected $hostname;
    protected $port = 389;
    protected $bind_dn;
    protected $bind_pw;
    protected $root_dn;
    protected $count;
    protected $reqCert = true;

    /**
     * Whether the bind on this connection was already performed
     *
     * @var bool
     */
    protected $bound = false;

    protected $root;

    /**
     * @var Capability
     */
    protected $capabilities;

    /**
     * @var bool
     */
    protected $discoverySuccess = false;

    /**
     * Constructor
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
        $this->encryption = $config->get('encryption');
        if ($this->encryption !== null) {
            $this->encryption = strtolower($this->encryption);
        }
        $this->reqCert = (bool) $config->get('reqcert', $this->reqCert);
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

        if ($this->pageControlAvailable($query)) {
            return $this->runPagedQuery($query, $fields);
        } else {
            return $this->runQuery($query, $fields);
        }
    }

    /**
     * Execute the given LDAP query and return the resulting entries
     *
     * @param Query $query      The query to execute
     * @param array $fields     The fields that will be fetched from the matches
     *
     * @return array            The matched entries
     * @throws LdapException
     */
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

    /**
     * Returns whether requesting the page control is available
     */
    protected function pageControlAvailable(Query $query)
    {
        return $this->capabilities->hasPagedResult() &&
               $query->getUsePagedResults() &&
               version_compare(PHP_VERSION, '5.4.0') >= 0;
    }

    /**
     * Execute the given LDAP query while requesting pagination control to separate
     * big responses into smaller chunks
     *
     * @param Query $query      The query to execute
     * @param array $fields     The fields that will be fetched from the matches
     * @param int   $page_size  The maximum page size, defaults to Connection::PAGE_SIZE
     *
     * @return array            The matched entries
     * @throws LdapException
     * @throws ProgrammingError When executed without available page controls (check with pageControlAvailable() )
     */
    protected function runPagedQuery(Query $query, $fields = array(), $pageSize = null)
    {
        if (! $this->pageControlAvailable($query)) {
            throw new ProgrammingError('LDAP: Page control not available.');
        }
        if (! isset($pageSize)) {
            $pageSize = static::PAGE_SIZE;
        }

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
            // do not set controlPageResult as a critical extension, since we still want the
            // possibillity  server to return an answer in case the pagination extension is missing.
            ldap_control_paged_result($this->ds, $pageSize, false, $cookie);

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

            if (false === @ldap_control_paged_result_response($this->ds, $results, $cookie)) {
                // If the page size is greater than or equal to the sizeLimit value, the server should ignore the
                // control as the request can be satisfied in a single page: https://www.ietf.org/rfc/rfc2696.txt
                // This applies no matter whether paged search requests are permitted or not. You're done once you
                // got everything you were out for.
                if (count($entries) !== $limit) {

                    // The server does not support pagination, but still returned a response by ignoring the
                    // pagedResultsControl. We output a warning to indicate that the pagination control was ignored.
                    Logger::warning('Unable to request paged LDAP results. Does the server allow paged search requests?');
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

        return $entries;
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
        if ($this->encryption === static::STARTTLS || $this->encryption === static::LDAPS) {
            $this->prepareTlsEnvironment();
        }

        $hostname = $this->hostname;
        if ($this->encryption === static::LDAPS) {
            $hostname = 'ldaps://' . $hostname;
        }

        $ds = ldap_connect($hostname, $this->port);
        try {
            $this->capabilities = $this->discoverCapabilities($ds);
            $this->discoverySuccess = true;
        } catch (LdapException $e) {
            Logger::debug($e);
            Logger::warning('LADP discovery failed, assuming default LDAP settings.');
            $this->capabilities = new Capability(); // create empty default capabilities
        }
        if ($this->encryption === static::STARTTLS) {
            $force_tls = false;
            if ($this->capabilities->hasStartTls()) {
                if (@ldap_start_tls($ds)) {
                    Logger::debug('LDAP STARTTLS succeeded');
                } else {
                    Logger::error('LDAP STARTTLS failed: %s', ldap_error($ds));
                    throw new LdapException('LDAP STARTTLS failed: %s', ldap_error($ds));
                }
            } elseif ($force_tls) {
                throw new LdapException('STARTTLS is required but not announced by %s', $this->hostname);
            } else {
                Logger::warning('LDAP STARTTLS enabled but not announced');
            }
        }

        // ldap_rename requires LDAPv3:
        if ($this->capabilities->hasLdapV3()) {
            if (! ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
                throw new LdapException('LDAPv3 is required');
            }
        } else {
            // TODO: remove this -> FORCING v3 for now
            ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
            Logger::warning('No LDAPv3 support detected');
        }

        // Not setting this results in "Operations error" on AD when using the whole domain as search base
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
        // ldap_set_option($ds, LDAP_OPT_DEREF, LDAP_DEREF_NEVER);
        return $ds;
    }

    protected function prepareTlsEnvironment()
    {
        // TODO: allow variable known CA location (system VS Icinga)
        if (Platform::isWindows()) {
            putenv('LDAPTLS_REQCERT=never');
        } else {
            if ($this->reqCert) {
                $ldap_conf = $this->getConfigDir('ldap_ca.conf');
            } else {
                $ldap_conf = $this->getConfigDir('ldap_nocert.conf');
            }
            putenv('LDAPRC=' . $ldap_conf); // TODO: Does not have any effect
            if (getenv('LDAPRC') !== $ldap_conf) {
                throw new LdapException('putenv failed');
            }
        }
    }

    /**
     * Get the capabilities of the connected server
     *
     * @return Capability   The capability object
     */
    public function getCapabilities()
    {
        return $this->capabilities;
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
     * @return Capability           The capabilities
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
                'supportedControl',
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

        $ldapAttributes = ldap_get_attributes($ds, $entry);
        $result = $this->cleanupAttributes($ldapAttributes);
        return new Capability($result);
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
