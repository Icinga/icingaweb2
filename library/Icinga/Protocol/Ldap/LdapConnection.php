<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use ArrayIterator;
use Exception;
use LogicException;
use stdClass;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use Icinga\Data\Selectable;
use Icinga\Data\Sortable;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Url;

/**
 * Encapsulate LDAP connections and query creation
 */
class LdapConnection implements Selectable, Inspectable
{
    /**
     * Indicates that the target object cannot be found
     *
     * @var int
     */
    const LDAP_NO_SUCH_OBJECT = 32;

    /**
     * Indicates that in a search operation, the size limit specified by the client or the server has been exceeded
     *
     * @var int
     */
    const LDAP_SIZELIMIT_EXCEEDED = 4;

    /**
     * Indicates that an LDAP server limit set by an administrative authority has been exceeded
     *
     * @var int
     */
    const LDAP_ADMINLIMIT_EXCEEDED = 11;

    /**
     * Indicates that during a bind operation one of the following occurred: The client passed either an incorrect DN
     * or password, or the password is incorrect because it has expired, intruder detection has locked the account, or
     * another similar reason.
     *
     * @var int
     */
    const LDAP_INVALID_CREDENTIALS = 49;

    /**
     * The default page size to use for paged queries
     *
     * @var int
     */
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
     * @var string
     */
    protected $encryption;

    /**
     * The LDAP link identifier being used
     *
     * @var resource
     */
    protected $ds;

    /**
     * The ip address, hostname or ldap URI being used to connect with the LDAP server
     *
     * @var string
     */
    protected $hostname;

    /**
     * The port being used to connect with the LDAP server
     *
     * @var int
     */
    protected $port;

    /**
     * The distinguished name being used to bind to the LDAP server
     *
     * @var string
     */
    protected $bindDn;

    /**
     * The password being used to bind to the LDAP server
     *
     * @var string
     */
    protected $bindPw;

    /**
     * The distinguished name being used as the base path for queries which do not provide one theirselves
     *
     * @var string
     */
    protected $rootDn;

    /**
     * Whether the bind on this connection has already been performed
     *
     * @var bool
     */
    protected $bound;

    /**
     * The current connection's root node
     *
     * @var Root
     */
    protected $root;

    /**
     * The properties and capabilities of the LDAP server
     *
     * @var LdapCapabilities
     */
    protected $capabilities;

    /**
     * Whether discovery was successful
     *
     * @var bool
     */
    protected $discoverySuccess;

    /**
     * The cause of the discovery's failure
     *
     * @var Exception|null
     */
    private $discoveryError;

    /**
     * Whether the current connection is encrypted
     *
     * @var bool
     */
    protected $encrypted = null;

    /**
     * Create a new connection object
     *
     * @param   ConfigObject    $config
     */
    public function __construct(ConfigObject $config)
    {
        $this->hostname = $config->hostname;
        $this->bindDn = $config->bind_dn;
        $this->bindPw = $config->bind_pw;
        $this->rootDn = $config->root_dn;
        $this->port = $config->get('port', 389);

        $this->encryption = $config->encryption;
        if ($this->encryption !== null) {
            $this->encryption = strtolower($this->encryption);
        }
    }

    /**
     * Return the ip address, hostname or ldap URI being used to connect with the LDAP server
     *
     * @return  string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Return the port being used to connect with the LDAP server
     *
     * @return  int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Return the distinguished name being used as the base path for queries which do not provide one theirselves
     *
     * @return  string
     */
    public function getDn()
    {
        return $this->rootDn;
    }

    /**
     * Return the root node for this connection
     *
     * @return  Root
     */
    public function root()
    {
        if ($this->root === null) {
            $this->root = Root::forConnection($this);
        }

        return $this->root;
    }

    /**
     * Return the LDAP link identifier being used
     *
     * Establishes a connection if necessary.
     *
     * @return  resource
     */
    public function getConnection()
    {
        if ($this->ds === null) {
            $this->ds = $this->prepareNewConnection();
        }

        return $this->ds;
    }

    /**
     * Return the capabilities of the current connection
     *
     * @return  LdapCapabilities
     */
    public function getCapabilities()
    {
        if ($this->capabilities === null) {
            try {
                $this->capabilities = LdapCapabilities::discoverCapabilities($this);
                $this->discoverySuccess = true;
                $this->discoveryError = null;
            } catch (LdapException $e) {
                Logger::debug($e);
                Logger::warning('LADP discovery failed, assuming default LDAP capabilities.');
                $this->capabilities = new LdapCapabilities(); // create empty default capabilities
                $this->discoverySuccess = false;
                $this->discoveryError = $e;
            }
        }

        return $this->capabilities;
    }

    /**
     * Return whether discovery was successful
     *
     * @return  bool    true if the capabilities were successfully determined, false if the capabilities were guessed
     */
    public function discoverySuccessful()
    {
        if ($this->discoverySuccess === null) {
            $this->getCapabilities(); // Initializes self::$discoverySuccess
        }

        return $this->discoverySuccess;
    }

    /**
     * Get discovery error if any
     *
     * @return Exception|null
     */
    public function getDiscoveryError()
    {
        return $this->discoveryError;
    }

    /**
     * Return whether the current connection is encrypted
     *
     * @return  bool
     */
    public function isEncrypted()
    {
        if ($this->encrypted === null) {
            return false;
        }

        return $this->encrypted;
    }

    /**
     * Establish a connection
     *
     * @throws  LdapException   In case the connection could not be established
     *
     * @deprecated              The connection is established lazily now
     */
    public function connect()
    {
        $this->getConnection();
    }

    /**
     * Perform a LDAP bind on the current connection
     *
     * @throws  LdapException   In case the LDAP bind was unsuccessful or insecure
     */
    public function bind()
    {
        if ($this->bound) {
            return $this;
        }

        $ds = $this->getConnection();

        $success = @ldap_bind($ds, $this->bindDn, $this->bindPw);
        if (! $success) {
            throw new LdapException(
                'LDAP bind (%s / %s) to %s with default port %s failed: %s',
                $this->bindDn,
                '***' /* $this->bindPw */,
                $this->hostname,
                $this->port,
                ldap_error($ds)
            );
        }

        $this->bound = true;
        return $this;
    }

    /**
     * Provide a query on this connection
     *
     * @return  LdapQuery
     */
    public function select()
    {
        return new LdapQuery($this);
    }

    /**
     * Fetch and return all rows of the given query's result set using an iterator
     *
     * @param   LdapQuery   $query  The query returning the result set
     *
     * @return  ArrayIterator
     */
    public function query(LdapQuery $query)
    {
        return new ArrayIterator($this->fetchAll($query));
    }

    /**
     * Count all rows of the given query's result set
     *
     * @param   LdapQuery   $query  The query returning the result set
     *
     * @return  int
     */
    public function count(LdapQuery $query)
    {
        $this->bind();

        if (($unfoldAttribute = $query->getUnfoldAttribute()) !== null) {
            $desiredColumns = $query->getColumns();
            if (isset($desiredColumns[$unfoldAttribute])) {
                $fields = array($unfoldAttribute => $desiredColumns[$unfoldAttribute]);
            } elseif (in_array($unfoldAttribute, $desiredColumns, true)) {
                $fields = array($unfoldAttribute);
            } else {
                throw new ProgrammingError(
                    'The attribute used to unfold a query\'s result must be selected'
                );
            }

            $res = $this->runQuery($query, $fields);
            return count($res);
        }

        $ds = $this->getConnection();
        $results = $this->ldapSearch($query, array('dn'));

        if ($results === false) {
            if (ldap_errno($ds) !== self::LDAP_NO_SUCH_OBJECT) {
                throw new LdapException(
                    'LDAP count query "%s" (base %s) failed: %s',
                    (string) $query,
                    $query->getBase() ?: $this->getDn(),
                    ldap_error($ds)
                );
            }
        }

        return ldap_count_entries($ds, $results);
    }

    /**
     * Retrieve an array containing all rows of the result set
     *
     * @param   LdapQuery   $query      The query returning the result set
     * @param   array       $fields     Request these attributes instead of the ones registered in the given query
     *
     * @return  array
     */
    public function fetchAll(LdapQuery $query, array $fields = null)
    {
        $this->bind();

        if ($query->getUsePagedResults()
            && version_compare(PHP_VERSION, '5.4.0') >= 0
            && $this->getCapabilities()->hasPagedResult()
        ) {
            return $this->runPagedQuery($query, $fields);
        } else {
            return $this->runQuery($query, $fields);
        }
    }

    /**
     * Fetch the first row of the result set
     *
     * @param   LdapQuery   $query      The query returning the result set
     * @param   array       $fields     Request these attributes instead of the ones registered in the given query
     *
     * @return  mixed
     */
    public function fetchRow(LdapQuery $query, array $fields = null)
    {
        $clonedQuery = clone $query;
        $clonedQuery->limit(1);
        $clonedQuery->setUsePagedResults(false);
        $results = $this->fetchAll($clonedQuery, $fields);
        return array_shift($results) ?: false;
    }

    /**
     * Fetch the first column of all rows of the result set as an array
     *
     * @param   LdapQuery   $query      The query returning the result set
     * @param   array       $fields     Request these attributes instead of the ones registered in the given query
     *
     * @return  array
     *
     * @throws  ProgrammingError        In case no attribute is being requested
     */
    public function fetchColumn(LdapQuery $query, array $fields = null)
    {
        if ($fields === null) {
            $fields = $query->getColumns();
        }

        if (empty($fields)) {
            throw new ProgrammingError('You must request at least one attribute when fetching a single column');
        }

        $alias = key($fields);
        $results = $this->fetchAll($query, array($alias => current($fields)));
        $column = is_int($alias) ? current($fields) : $alias;
        $values = array();
        foreach ($results as $row) {
            if (isset($row->$column)) {
                $values[] = $row->$column;
            }
        }

        return $values;
    }

    /**
     * Fetch the first column of the first row of the result set
     *
     * @param   LdapQuery   $query      The query returning the result set
     * @param   array       $fields     Request these attributes instead of the ones registered in the given query
     *
     * @return  string
     */
    public function fetchOne(LdapQuery $query, array $fields = null)
    {
        $row = $this->fetchRow($query, $fields);
        if ($row === false) {
            return false;
        }

        $values = get_object_vars($row);
        if (empty($values)) {
            return false;
        }

        if ($fields === null) {
            // Fetch the desired columns from the query if not explicitly overriden in the method's parameter
            $fields = $query->getColumns();
        }

        if (empty($fields)) {
            // The desired columns may be empty independently whether provided by the query or the method's parameter
            return array_shift($values);
        }

        $alias = key($fields);
        return $values[is_string($alias) ? $alias : $fields[$alias]];
    }

    /**
     * Fetch all rows of the result set as an array of key-value pairs
     *
     * The first column is the key, the second column is the value.
     *
     * @param   LdapQuery   $query      The query returning the result set
     * @param   array       $fields     Request these attributes instead of the ones registered in the given query
     *
     * @return  array
     *
     * @throws  ProgrammingError        In case there are less than two attributes being requested
     */
    public function fetchPairs(LdapQuery $query, array $fields = null)
    {
        if ($fields === null) {
            $fields = $query->getColumns();
        }

        if (count($fields) < 2) {
            throw new ProgrammingError('You are required to request at least two attributes');
        }

        $columns = $desiredColumnNames = array();
        foreach ($fields as $alias => $column) {
            if (is_int($alias)) {
                $columns[] = $column;
                $desiredColumnNames[] = $column;
            } else {
                $columns[$alias] = $column;
                $desiredColumnNames[] = $alias;
            }

            if (count($desiredColumnNames) === 2) {
                break;
            }
        }

        $results = $this->fetchAll($query, $columns);
        $pairs = array();
        foreach ($results as $row) {
            $colOne = $desiredColumnNames[0];
            $colTwo = $desiredColumnNames[1];
            $pairs[$row->$colOne] = $row->$colTwo;
        }

        return $pairs;
    }

    /**
     * Fetch an LDAP entry by its DN
     *
     * @param  string        $dn
     * @param  array|null    $fields
     *
     * @return StdClass|bool
     */
    public function fetchByDn($dn, array $fields = null)
    {
        return $this->select()
            ->from('*', $fields)
            ->setBase($dn)
            ->setScope('base')
            ->fetchRow();
    }

    /**
     * Test the given LDAP credentials by establishing a connection and attempting a LDAP bind
     *
     * @param   string  $bindDn
     * @param   string  $bindPw
     *
     * @return  bool                Whether the given credentials are valid
     *
     * @throws  LdapException       In case an error occured while establishing the connection or attempting the bind
     */
    public function testCredentials($bindDn, $bindPw)
    {
        $ds = $this->getConnection();
        $success = @ldap_bind($ds, $bindDn, $bindPw);
        if (! $success) {
            if (ldap_errno($ds) === self::LDAP_INVALID_CREDENTIALS) {
                Logger::debug(
                    'Testing LDAP credentials (%s / %s) failed: %s',
                    $bindDn,
                    '***',
                    ldap_error($ds)
                );
                return false;
            }

            throw new LdapException(ldap_error($ds));
        }

        return true;
    }

    /**
     * Return whether an entry identified by the given distinguished name exists
     *
     * @param   string  $dn
     *
     * @return  bool
     */
    public function hasDn($dn)
    {
        $ds = $this->getConnection();
        $this->bind();

        $result = ldap_read($ds, $dn, '(objectClass=*)', array('objectClass'));
        return ldap_count_entries($ds, $result) > 0;
    }

    /**
     * Delete a root entry and all of its children identified by the given distinguished name
     *
     * @param   string  $dn
     *
     * @return  bool
     *
     * @throws  LdapException   In case an error occured while deleting an entry
     */
    public function deleteRecursively($dn)
    {
        $ds = $this->getConnection();
        $this->bind();

        $result = @ldap_list($ds, $dn, '(objectClass=*)', array('objectClass'));
        if ($result === false) {
            if (ldap_errno($ds) === self::LDAP_NO_SUCH_OBJECT) {
                return false;
            }

            throw new LdapException('LDAP list for "%s" failed: %s', $dn, ldap_error($ds));
        }

        $children = ldap_get_entries($ds, $result);
        for ($i = 0; $i < $children['count']; $i++) {
            $result = $this->deleteRecursively($children[$i]['dn']);
            if (! $result) {
                // TODO: return result code, if delete fails
                throw new LdapException('Recursively deleting "%s" failed', $dn);
            }
        }

        return $this->deleteDn($dn);
    }

    /**
     * Delete a single entry identified by the given distinguished name
     *
     * @param   string  $dn
     *
     * @return  bool
     *
     * @throws  LdapException   In case an error occured while deleting the entry
     */
    public function deleteDn($dn)
    {
        $ds = $this->getConnection();
        $this->bind();

        $result = @ldap_delete($ds, $dn);
        if ($result === false) {
            if (ldap_errno($ds) === self::LDAP_NO_SUCH_OBJECT) {
                return false; // TODO: Isn't it a success if something i'd like to remove is not existing at all???
            }

            throw new LdapException('LDAP delete for "%s" failed: %s', $dn, ldap_error($ds));
        }

        return true;
    }

    /**
     * Fetch the distinguished name of the result of the given query
     *
     * @param   LdapQuery   $query  The query returning the result set
     *
     * @return  string              The distinguished name, or false when the given query yields no results
     *
     * @throws  LdapException       In case the query yields multiple results
     */
    public function fetchDn(LdapQuery $query)
    {
        $rows = $this->fetchAll($query, array());
        if (count($rows) > 1) {
            throw new LdapException('Cannot fetch single DN for %s', $query);
        }

        return key($rows);
    }

    /**
     * Run the given LDAP query and return the resulting entries
     *
     * @param   LdapQuery   $query      The query to fetch results with
     * @param   array       $fields     Request these attributes instead of the ones registered in the given query
     *
     * @return  array
     *
     * @throws  LdapException           In case an error occured while fetching the results
     */
    protected function runQuery(LdapQuery $query, array $fields = null)
    {
        $limit = $query->getLimit();
        $offset = $query->hasOffset() ? $query->getOffset() : 0;

        if ($fields === null) {
            $fields = $query->getColumns();
        }

        $ds = $this->getConnection();

        $serverSorting = $this->getCapabilities()->hasOid(LdapCapabilities::LDAP_SERVER_SORT_OID);

        if ($query->hasOrder()) {
            if ($serverSorting) {
                ldap_set_option($ds, LDAP_OPT_SERVER_CONTROLS, array(
                    array(
                        'oid'   => LdapCapabilities::LDAP_SERVER_SORT_OID,
                        'value' => $this->encodeSortRules($query->getOrder())
                    )
                ));
            } elseif (! empty($fields)) {
                foreach ($query->getOrder() as $rule) {
                    if (! in_array($rule[0], $fields, true)) {
                        $fields[] = $rule[0];
                    }
                }
            }
        }

        $unfoldAttribute = $query->getUnfoldAttribute();
        if ($unfoldAttribute) {
            foreach ($query->getFilter()->listFilteredColumns() as $filterColumn) {
                $fieldKey = array_search($filterColumn, $fields, true);
                if ($fieldKey === false || is_string($fieldKey)) {
                    $fields[] = $filterColumn;
                }
            }
        }

        $results = $this->ldapSearch(
            $query,
            array_values($fields),
            0,
            ($serverSorting || ! $query->hasOrder()) && $limit ? $offset + $limit : 0
        );
        if ($results === false) {
            if (ldap_errno($ds) === self::LDAP_NO_SUCH_OBJECT) {
                return array();
            }

            throw new LdapException(
                'LDAP query "%s" (base %s) failed. Error: %s',
                $query,
                $query->getBase() ?: $this->rootDn,
                ldap_error($ds)
            );
        } elseif (ldap_count_entries($ds, $results) === 0) {
            return array();
        }

        $count = 0;
        $entries = array();
        $entry = ldap_first_entry($ds, $results);
        do {
            if ($unfoldAttribute) {
                $rows = $this->cleanupAttributes(ldap_get_attributes($ds, $entry), $fields, $unfoldAttribute);
                if (is_array($rows)) {
                    // TODO: Register the DN the same way as a section name in the ArrayDatasource!
                    foreach ($rows as $row) {
                        if ($query->getFilter()->matches($row)) {
                            $count += 1;
                            if (! $serverSorting || $offset === 0 || $offset < $count) {
                                $entries[] = $row;
                            }

                            if ($serverSorting && $limit > 0 && $limit === count($entries)) {
                                break;
                            }
                        }
                    }
                } else {
                    $count += 1;
                    if (! $serverSorting || $offset === 0 || $offset < $count) {
                        $entries[ldap_get_dn($ds, $entry)] = $rows;
                    }
                }
            } else {
                $count += 1;
                if (! $serverSorting || $offset === 0 || $offset < $count) {
                    $entries[ldap_get_dn($ds, $entry)] = $this->cleanupAttributes(
                        ldap_get_attributes($ds, $entry),
                        $fields
                    );
                }
            }
        } while ((! $serverSorting || $limit === 0 || $limit !== count($entries))
            && ($entry = ldap_next_entry($ds, $entry))
        );

        if (! $serverSorting) {
            if ($query->hasOrder()) {
                uasort($entries, array($query, 'compare'));
            }

            if ($limit && $count > $limit) {
                $entries = array_splice($entries, $query->hasOffset() ? $query->getOffset() : 0, $limit);
            }
        }

        ldap_free_result($results);
        return $entries;
    }

    /**
     * Run the given LDAP query and return the resulting entries
     *
     * This utilizes paged search requests as defined in RFC 2696.
     *
     * @param   LdapQuery   $query      The query to fetch results with
     * @param   array       $fields     Request these attributes instead of the ones registered in the given query
     * @param   int         $pageSize   The maximum page size, defaults to self::PAGE_SIZE
     *
     * @return  array
     *
     * @throws  LdapException           In case an error occured while fetching the results
     */
    protected function runPagedQuery(LdapQuery $query, array $fields = null, $pageSize = null)
    {
        if ($pageSize === null) {
            $pageSize = static::PAGE_SIZE;
        }

        $limit = $query->getLimit();
        $offset = $query->hasOffset() ? $query->getOffset() : 0;

        if ($fields === null) {
            $fields = $query->getColumns();
        }

        $ds = $this->getConnection();

        $serverSorting = false;//$this->getCapabilities()->hasOid(LdapCapabilities::LDAP_SERVER_SORT_OID);
        if (! $serverSorting && $query->hasOrder() && ! empty($fields)) {
            foreach ($query->getOrder() as $rule) {
                if (! in_array($rule[0], $fields, true)) {
                    $fields[] = $rule[0];
                }
            }
        }

        $unfoldAttribute = $query->getUnfoldAttribute();
        if ($unfoldAttribute) {
            foreach ($query->getFilter()->listFilteredColumns() as $filterColumn) {
                $fieldKey = array_search($filterColumn, $fields, true);
                if ($fieldKey === false || is_string($fieldKey)) {
                    $fields[] = $filterColumn;
                }
            }
        }

        $count = 0;
        $cookie = '';
        $entries = array();
        do {
            // Do not request the pagination control as a critical extension, as we want the
            // server to return results even if the paged search request cannot be satisfied
            ldap_control_paged_result($ds, $pageSize, false, $cookie);

            if ($serverSorting && $query->hasOrder()) {
                ldap_set_option($ds, LDAP_OPT_SERVER_CONTROLS, array(
                    array(
                        'oid'   => LdapCapabilities::LDAP_SERVER_SORT_OID,
                        'value' => $this->encodeSortRules($query->getOrder())
                    )
                ));
            }

            $results = $this->ldapSearch(
                $query,
                array_values($fields),
                0,
                ($serverSorting || ! $query->hasOrder()) && $limit ? $offset + $limit : 0
            );
            if ($results === false) {
                if (ldap_errno($ds) === self::LDAP_NO_SUCH_OBJECT) {
                    break;
                }

                throw new LdapException(
                    'LDAP query "%s" (base %s) failed. Error: %s',
                    (string) $query,
                    $query->getBase() ?: $this->getDn(),
                    ldap_error($ds)
                );
            } elseif (ldap_count_entries($ds, $results) === 0) {
                if (in_array(
                    ldap_errno($ds),
                    array(static::LDAP_SIZELIMIT_EXCEEDED, static::LDAP_ADMINLIMIT_EXCEEDED),
                    true
                )) {
                    Logger::warning(
                        'Unable to request more than %u results. Does the server allow paged search requests? (%s)',
                        $count,
                        ldap_error($ds)
                    );
                }

                break;
            }

            $entry = ldap_first_entry($ds, $results);
            do {
                if ($unfoldAttribute) {
                    $rows = $this->cleanupAttributes(ldap_get_attributes($ds, $entry), $fields, $unfoldAttribute);
                    if (is_array($rows)) {
                        // TODO: Register the DN the same way as a section name in the ArrayDatasource!
                        foreach ($rows as $row) {
                            if ($query->getFilter()->matches($row)) {
                                $count += 1;
                                if (! $serverSorting || $offset === 0 || $offset < $count) {
                                    $entries[] = $row;
                                }

                                if ($serverSorting && $limit > 0 && $limit === count($entries)) {
                                    break;
                                }
                            }
                        }
                    } else {
                        $count += 1;
                        if (! $serverSorting || $offset === 0 || $offset < $count) {
                            $entries[ldap_get_dn($ds, $entry)] = $rows;
                        }
                    }
                } else {
                    $count += 1;
                    if (! $serverSorting || $offset === 0 || $offset < $count) {
                        $entries[ldap_get_dn($ds, $entry)] = $this->cleanupAttributes(
                            ldap_get_attributes($ds, $entry),
                            $fields
                        );
                    }
                }
            } while ((! $serverSorting || $limit === 0 || $limit !== count($entries))
                && ($entry = ldap_next_entry($ds, $entry))
            );

            if (false === @ldap_control_paged_result_response($ds, $results, $cookie)) {
                // If the page size is greater than or equal to the sizeLimit value, the server should ignore the
                // control as the request can be satisfied in a single page: https://www.ietf.org/rfc/rfc2696.txt
                // This applies no matter whether paged search requests are permitted or not. You're done once you
                // got everything you were out for.
                if ($serverSorting && count($entries) !== $limit) {
                    // The server does not support pagination, but still returned a response by ignoring the
                    // pagedResultsControl. We output a warning to indicate that the pagination control was ignored.
                    Logger::warning(
                        'Unable to request paged LDAP results. Does the server allow paged search requests?'
                    );
                }
            }

            ldap_free_result($results);
        } while ($cookie && (! $serverSorting || $limit === 0 || count($entries) < $limit));

        if ($cookie) {
            // A sequence of paged search requests is abandoned by the client sending a search request containing a
            // pagedResultsControl with the size set to zero (0) and the cookie set to the last cookie returned by
            // the server: https://www.ietf.org/rfc/rfc2696.txt
            ldap_control_paged_result($ds, 0, false, $cookie);
            // Returns no entries, due to the page size
            ldap_search($ds, $query->getBase() ?: $this->getDn(), (string) $query);
        }

        if (! $serverSorting) {
            if ($query->hasOrder()) {
                uasort($entries, array($query, 'compare'));
            }

            if ($limit && $count > $limit) {
                $entries = array_splice($entries, $query->hasOffset() ? $query->getOffset() : 0, $limit);
            }
        }

        return $entries;
    }

    /**
     * Clean up the given attributes and return them as simple object
     *
     * Applies column aliases, aggregates/unfolds multi-value attributes
     * as array and sets null for each missing attribute.
     *
     * @param   array   $attributes
     * @param   array   $requestedFields
     * @param   string  $unfoldAttribute
     *
     * @return  object|array    An array in case the object has been unfolded
     */
    public function cleanupAttributes($attributes, array $requestedFields, $unfoldAttribute = null)
    {
        // In case the result contains attributes with a differing case than the requested fields, it is
        // necessary to create another array to map attributes case insensitively to their requested counterparts.
        // This does also apply the virtual alias handling. (Since an LDAP server does not handle such)
        $loweredFieldMap = array();
        foreach ($requestedFields as $alias => $name) {
            $loweredName = strtolower($name);
            if (isset($loweredFieldMap[$loweredName])) {
                if (! is_array($loweredFieldMap[$loweredName])) {
                    $loweredFieldMap[$loweredName] = array($loweredFieldMap[$loweredName]);
                }

                $loweredFieldMap[$loweredName][] = is_string($alias) ? $alias : $name;
            } else {
                $loweredFieldMap[$loweredName] = is_string($alias) ? $alias : $name;
            }
        }

        $cleanedAttributes = array();
        for ($i = 0; $i < $attributes['count']; $i++) {
            $attribute_name = $attributes[$i];
            if ($attributes[$attribute_name]['count'] === 1) {
                $attribute_value = $attributes[$attribute_name][0];
            } else {
                $attribute_value = array();
                for ($j = 0; $j < $attributes[$attribute_name]['count']; $j++) {
                    $attribute_value[] = $attributes[$attribute_name][$j];
                }
            }

            $requestedAttributeName = isset($loweredFieldMap[strtolower($attribute_name)])
                ? $loweredFieldMap[strtolower($attribute_name)]
                : $attribute_name;
            if (is_array($requestedAttributeName)) {
                foreach ($requestedAttributeName as $requestedName) {
                    $cleanedAttributes[$requestedName] = $attribute_value;
                }
            } else {
                $cleanedAttributes[$requestedAttributeName] = $attribute_value;
            }
        }

        // The result may not contain all requested fields, so populate the cleaned
        // result with the missing fields and their value being set to null
        foreach ($requestedFields as $alias => $name) {
            if (! is_string($alias)) {
                $alias = $name;
            }

            if (! array_key_exists($alias, $cleanedAttributes)) {
                $cleanedAttributes[$alias] = null;
                Logger::debug('LDAP query result does not provide the requested field "%s"', $name);
            }
        }

        if ($unfoldAttribute !== null
            && isset($cleanedAttributes[$unfoldAttribute])
            && is_array($cleanedAttributes[$unfoldAttribute])
        ) {
            $siblings = array();
            foreach ($loweredFieldMap as $loweredName => $requestedNames) {
                if (is_array($requestedNames) && in_array($unfoldAttribute, $requestedNames, true)) {
                    $siblings = array_diff($requestedNames, array($unfoldAttribute));
                    break;
                }
            }

            $values = $cleanedAttributes[$unfoldAttribute];
            unset($cleanedAttributes[$unfoldAttribute]);
            $baseRow = (object) $cleanedAttributes;
            $rows = array();
            foreach ($values as $value) {
                $row = clone $baseRow;
                $row->{$unfoldAttribute} = $value;
                foreach ($siblings as $sibling) {
                    $row->{$sibling} = $value;
                }

                $rows[] = $row;
            }

            return $rows;
        }

        return (object) $cleanedAttributes;
    }

    /**
     * Encode the given array of sort rules as ASN.1 octet stream according to RFC 2891
     *
     * @param   array   $sortRules
     *
     * @return  string  Binary representation of the octet stream
     */
    protected function encodeSortRules(array $sortRules)
    {
        $sequenceOf = '';

        foreach ($sortRules as $rule) {
            if ($rule[1] === Sortable::SORT_DESC) {
                $reversed = '8101ff';
            } else {
                $reversed = '';
            }

            $attributeType = unpack('H*', $rule[0]);
            $attributeType = $attributeType[1];
            $attributeOctets = strlen($attributeType) / 2;
            if ($attributeOctets >= 127) {
                // Use the indefinite form of the length octets (the long form would be another option)
                $attributeType = '0440' . $attributeType . '0000';
            } else {
                $attributeType = '04' . str_pad(dechex($attributeOctets), 2, '0', STR_PAD_LEFT) . $attributeType;
            }

            $sequence = $attributeType . $reversed;
            $sequenceOctects = strlen($sequence) / 2;
            if ($sequenceOctects >= 127) {
                $sequence = '3040' . $sequence . '0000';
            } else {
                $sequence = '30' . str_pad(dechex($sequenceOctects), 2, '0', STR_PAD_LEFT) . $sequence;
            }

            $sequenceOf .= $sequence;
        }

        $sequenceOfOctets = strlen($sequenceOf) / 2;
        if ($sequenceOfOctets >= 127) {
            $sequenceOf = '3040' . $sequenceOf . '0000';
        } else {
            $sequenceOf = '30' . str_pad(dechex($sequenceOfOctets), 2, '0', STR_PAD_LEFT) . $sequenceOf;
        }

        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            return hex2bin($sequenceOf);
        } else {
            return pack('H*', $sequenceOf);
        }
    }

    /**
     * Prepare and establish a connection with the LDAP server
     *
     * @param   Inspection  $info   Optional inspection to fill with diagnostic info
     *
     * @return  resource            A LDAP link identifier
     *
     * @throws  LdapException       In case the connection is not possible
     */
    protected function prepareNewConnection(Inspection $info = null)
    {
        if (! isset($info)) {
            $info = new Inspection('');
        }

        $hostname = $this->hostname;
        if ($this->encryption === static::LDAPS) {
            $info->write('Connect using LDAPS');
            $ldapUrls = explode(' ', $hostname);
            if (count($ldapUrls) > 1) {
                foreach ($ldapUrls as & $uri) {
                    if (strpos($uri, '://') === false) {
                        $uri = 'ldaps://' . $uri;
                    }
                }

                $hostname = implode(' ', $ldapUrls);
            } else {
                $hostname = 'ldaps://' . $hostname;
            }
        }

        $ds = ldap_connect($hostname, $this->port);

        // Usage of ldap_rename, setting LDAP_OPT_REFERRALS to 0 or using STARTTLS requires LDAPv3.
        // If this does not work we're probably not in a PHP 5.3+ environment as it is VERY
        // unlikely that the server complains about it by itself prior to a bind request
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Not setting this results in "Operations error" on AD when using the whole domain as search base
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        if ($this->encryption === static::STARTTLS) {
            $this->encrypted = true;
            $info->write('Connect using STARTTLS');
            if (! ldap_start_tls($ds)) {
                throw new LdapException('LDAP STARTTLS failed: %s', ldap_error($ds));
            }
        } elseif ($this->encryption !== static::LDAPS) {
            $this->encrypted = false;
            $info->write('Connect without encryption');
        }

        return $ds;
    }

    /**
     * Perform a LDAP search and return the result
     *
     * @param   LdapQuery   $query
     * @param   array       $attributes     An array of the required attributes
     * @param   int         $attrsonly      Should be set to 1 if only attribute types are wanted
     * @param   int         $sizelimit      Enables you to limit the count of entries fetched
     * @param   int         $timelimit      Sets the number of seconds how long is spend on the search
     * @param   int         $deref
     *
     * @return  resource|bool               A search result identifier or false on error
     *
     * @throws  LogicException              If the LDAP query search scope is unsupported
     */
    public function ldapSearch(
        LdapQuery $query,
        array $attributes = null,
        $attrsonly = 0,
        $sizelimit = 0,
        $timelimit = 0,
        $deref = LDAP_DEREF_NEVER
    ) {
        $queryString = (string) $query;
        $baseDn = $query->getBase() ?: $this->getDn();
        $scope = $query->getScope();

        if (Logger::getInstance()->getLevel() === Logger::DEBUG) {
            // We're checking the level by ourselves to avoid rendering the ldapsearch commandline for nothing
            $starttlsParam = $this->encryption === static::STARTTLS ? ' -ZZ' : '';

            $ldapUrls = array();
            $defaultScheme = $this->encryption === static::LDAPS ? 'ldaps://' : 'ldap://';
            foreach (explode(' ', $this->hostname) as $uri) {
                $url = Url::fromPath($uri);
                if (! $url->getScheme()) {
                    $uri = $defaultScheme . $uri . ($this->port ? ':' . $this->port : '');
                } else {
                    if ($url->getPort() === null) {
                        $url->setPort($this->port);
                    }

                    $uri = $url->getAbsoluteUrl();
                }

                $ldapUrls[] = $uri;
            }

            $bindParams = '';
            if ($this->bound) {
                $bindParams = ' -D "' . $this->bindDn . '"' . ($this->bindPw ? ' -W' : '');
            }

            if ($deref === LDAP_DEREF_NEVER) {
                $derefName = 'never';
            } elseif ($deref === LDAP_DEREF_ALWAYS) {
                $derefName = 'always';
            } elseif ($deref === LDAP_DEREF_SEARCHING) {
                $derefName = 'search';
            } else { // $deref === LDAP_DEREF_FINDING
                $derefName = 'find';
            }

            Logger::debug("Issueing LDAP search. Use '%s' to reproduce.", sprintf(
                'ldapsearch -P 3%s -H "%s"%s -b "%s" -s "%s" -z %u -l %u -a "%s"%s%s%s',
                $starttlsParam,
                implode(' ', $ldapUrls),
                $bindParams,
                $baseDn,
                $scope,
                $sizelimit,
                $timelimit,
                $derefName,
                $attrsonly ? ' -A' : '',
                $queryString ? ' "' . $queryString . '"' : '',
                $attributes ? ' "' . join('" "', $attributes) . '"' : ''
            ));
        }

        switch ($scope) {
            case LdapQuery::SCOPE_SUB:
                $function = 'ldap_search';
                break;
            case LdapQuery::SCOPE_ONE:
                $function = 'ldap_list';
                break;
            case LdapQuery::SCOPE_BASE:
                $function = 'ldap_read';
                break;
            default:
                throw new LogicException('LDAP scope %s not supported by ldapSearch', $scope);
        }

        return @$function(
            $this->getConnection(),
            $baseDn,
            $queryString,
            $attributes,
            $attrsonly,
            $sizelimit,
            $timelimit,
            $deref
        );
    }

    /**
     * Create an LDAP entry
     *
     * @param   string  $dn             The distinguished name to use
     * @param   array   $attributes     The entry's attributes
     *
     * @return  bool                    Whether the operation was successful
     */
    public function addEntry($dn, array $attributes)
    {
        return ldap_add($this->getConnection(), $dn, $attributes);
    }

    /**
     * Modify an LDAP entry
     *
     * @param   string  $dn             The distinguished name to use
     * @param   array   $attributes     The attributes to update the entry with
     *
     * @return  bool                    Whether the operation was successful
     */
    public function modifyEntry($dn, array $attributes)
    {
        return ldap_modify($this->getConnection(), $dn, $attributes);
    }

    /**
     * Change the distinguished name of an LDAP entry
     *
     * @param   string  $dn             The entry's current distinguished name
     * @param   string  $newRdn         The new relative distinguished name
     * @param   string  $newParentDn    The new parent or superior entry's distinguished name
     *
     * @return  resource                The resulting search result identifier
     *
     * @throws  LdapException           In case an error occured
     */
    public function moveEntry($dn, $newRdn, $newParentDn)
    {
        $ds = $this->getConnection();
        $result = ldap_rename($ds, $dn, $newRdn, $newParentDn, false);
        if ($result === false) {
            throw new LdapException('Could not move entry "%s" to "%s": %s', $dn, $newRdn, ldap_error($ds));
        }

        return $result;
    }

    /**
     * Return the LDAP specific configuration directory with the given relative path being appended
     *
     * @param   string  $sub
     *
     * @return  string
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
     * Render and return a valid LDAP filter representation of the given filter
     *
     * @param   Filter  $filter
     * @param   int     $level
     *
     * @return  string
     */
    public function renderFilter(Filter $filter, $level = 0)
    {
        if ($filter->isExpression()) {
            /** @var $filter FilterExpression */
            return $this->renderFilterExpression($filter);
        }

        /** @var $filter FilterChain */
        $parts = array();
        foreach ($filter->filters() as $filterPart) {
            $part = $this->renderFilter($filterPart, $level + 1);
            if ($part) {
                $parts[] = $part;
            }
        }

        if (empty($parts)) {
            return '';
        }

        $format = '%1$s(%2$s)';
        if (count($parts) === 1) {
            $format = '%2$s';
        }
        if ($level === 0) {
            $format = '(' . $format . ')';
        }

        return sprintf($format, $filter->getOperatorSymbol(), implode(')(', $parts));
    }

    /**
     * Render and return a valid LDAP filter expression of the given filter
     *
     * @param   FilterExpression    $filter
     *
     * @return  string
     */
    protected function renderFilterExpression(FilterExpression $filter)
    {
        $column = $filter->getColumn();
        $sign = $filter->getSign();
        $expression = $filter->getExpression();
        $format = '%1$s%2$s%3$s';

        if ($expression === null || $expression === true) {
            $expression = '*';
        } elseif (is_array($expression)) {
            $seqFormat = '|(%s)';
            if ($sign === '!=') {
                $seqFormat = '!(' . $seqFormat . ')';
                $sign = '=';
            }

            $seqParts = array();
            foreach ($expression as $expressionValue) {
                $seqParts[] = sprintf(
                    $format,
                    LdapUtils::quoteForSearch($column),
                    $sign,
                    LdapUtils::quoteForSearch($expressionValue, true)
                );
            }

            return sprintf($seqFormat, implode(')(', $seqParts));
        }

        if ($sign === '!=') {
            $format = '!(%1$s=%3$s)';
        }

        return sprintf(
            $format,
            LdapUtils::quoteForSearch($column),
            $sign,
            LdapUtils::quoteForSearch($expression, true)
        );
    }

    /**
     * Inspect if this LDAP Connection is working as expected
     *
     * Check if connection, bind and encryption is working as expected and get additional
     * information about the used
     *
     * @return  Inspection  Inspection result
     */
    public function inspect()
    {
        $insp = new Inspection('Ldap Connection');

        // Try to connect to the server with the given connection parameters
        try {
            $ds = $this->prepareNewConnection($insp);
        } catch (Exception $e) {
            if ($this->encryption === 'starttls') {
                // The Exception does not return any proper error messages in case of certificate errors. Connecting
                // by STARTTLS will usually fail at this point when the certificate is unknown,
                // so at least try to give some hints.
                $insp->write('NOTE: There might be an issue with the chosen encryption. Ensure that the LDAP-Server ' .
                    'supports STARTTLS and that the LDAP-Client is configured to accept its certificate.');
            }
            return $insp->error($e->getMessage());
        }

        // Try a bind-command with the given user credentials, this must not fail
        $success = @ldap_bind($ds, $this->bindDn, $this->bindPw);
        $msg = sprintf(
            'LDAP bind (%s / %s) to %s with default port %s',
            $this->bindDn,
            '***' /* $this->bindPw */,
            $this->hostname,
            $this->port
        );
        if (! $success) {
            // ldap_error does not return any proper error messages in case of certificate errors. Connecting
            // by LDAPS will usually fail at this point when the certificate is unknown, so at least try to give
            // some hints.
            if ($this->encryption === 'ldaps') {
                $insp->write('NOTE: There might be an issue with the chosen encryption. Ensure that the LDAP-Server ' .
                    ' supports LDAPS and that the LDAP-Client is configured to accept its certificate.');
            }
            return $insp->error(sprintf('%s failed: %s', $msg, ldap_error($ds)));
        }
        $insp->write(sprintf($msg . ' successful'));

        // Try to execute a schema discovery this may fail if schema discovery is not supported
        try {
            $cap = LdapCapabilities::discoverCapabilities($this);
            $discovery = new Inspection('Discovery Results');
            $discovery->write($cap->getVendor());
            $version = $cap->getVersion();
            if (isset($version)) {
                $discovery->write($version);
            }
            $discovery->write('Supports STARTTLS: ' . ($cap->hasStartTls() ? 'True' : 'False'));
            $discovery->write('Default naming context: ' . $cap->getDefaultNamingContext());
            $insp->write($discovery);
        } catch (Exception $e) {
            $insp->write('Schema discovery not possible: ' . $e->getMessage());
        }
        return $insp;
    }
}
