<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use Exception;
use ArrayIterator;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Application\Platform;
use Icinga\Data\ConfigObject;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use Icinga\Data\Selectable;
use Icinga\Data\Sortable;
use Icinga\Exception\InspectionException;
use Icinga\Exception\ProgrammingError;
use Icinga\Protocol\Ldap\LdapException;

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
            } catch (LdapException $e) {
                Logger::debug($e);
                Logger::warning('LADP discovery failed, assuming default LDAP capabilities.');
                $this->capabilities = new LdapCapabilities(); // create empty default capabilities
                $this->discoverySuccess = false;
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
                'LDAP connection to %s:%s (%s / %s) failed: %s',
                $this->hostname,
                $this->port,
                $this->bindDn,
                '***' /* $this->bindPw */,
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

        $res = $this->runQuery($query, array());
        return count($res);
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
        $row = (array) $this->fetchRow($query, $fields);
        return array_shift($row) ?: false;
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
        $this->bind();

        $ds = $this->getConnection();
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
        $this->bind();

        $ds = $this->getConnection();
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
        $this->bind();

        $ds = $this->getConnection();
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
        $offset = $query->hasOffset() ? $query->getOffset() - 1 : 0;

        if ($fields === null) {
            $fields = $query->getColumns();
        }

        $ds = $this->getConnection();

        $serverSorting = false;//$this->capabilities->hasOid(Capability::LDAP_SERVER_SORT_OID);
        if ($serverSorting && $query->hasOrder()) {
            ldap_set_option($ds, LDAP_OPT_SERVER_CONTROLS, array(
                array(
                    'oid'   => LdapCapabilities::LDAP_SERVER_SORT_OID,
                    'value' => $this->encodeSortRules($query->getOrder())
                )
            ));
        } elseif ($query->hasOrder()) {
            foreach ($query->getOrder() as $rule) {
                if (! in_array($rule[0], $fields)) {
                    $fields[] = $rule[0];
                }
            }
        }

        $results = @ldap_search(
            $ds,
            $query->getBase() ?: $this->rootDn,
            (string) $query,
            array_values($fields),
            0, // Attributes and values
            $serverSorting && $limit ? $offset + $limit : 0
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
            $count += 1;
            if (! $serverSorting || $offset === 0 || $offset < $count) {
                $entries[ldap_get_dn($ds, $entry)] = $this->cleanupAttributes(
                    ldap_get_attributes($ds, $entry),
                    array_flip($fields)
                );
            }
        } while ((! $serverSorting || $limit === 0 || $limit !== count($entries))
            && ($entry = ldap_next_entry($ds, $entry))
        );

        if (! $serverSorting && $query->hasOrder()) {
            uasort($entries, array($query, 'compare'));
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
        $offset = $query->hasOffset() ? $query->getOffset() - 1 : 0;
        $queryString = (string) $query;
        $base = $query->getBase() ?: $this->rootDn;

        if ($fields === null) {
            $fields = $query->getColumns();
        }

        $ds = $this->getConnection();

        $serverSorting = false;//$this->capabilities->hasOid(Capability::LDAP_SERVER_SORT_OID);
        if ($serverSorting && $query->hasOrder()) {
            ldap_set_option($ds, LDAP_OPT_SERVER_CONTROLS, array(
                array(
                    'oid'   => LdapCapabilities::LDAP_SERVER_SORT_OID,
                    'value' => $this->encodeSortRules($query->getOrder())
                )
            ));
        } elseif ($query->hasOrder()) {
            foreach ($query->getOrder() as $rule) {
                if (! in_array($rule[0], $fields)) {
                    $fields[] = $rule[0];
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

            $results = @ldap_search(
                $ds,
                $base,
                $queryString,
                array_values($fields),
                0, // Attributes and values
                $serverSorting && $limit ? $offset + $limit : 0
            );
            if ($results === false) {
                if (ldap_errno($ds) === self::LDAP_NO_SUCH_OBJECT) {
                    break;
                }

                throw new LdapException(
                    'LDAP query "%s" (base %s) failed. Error: %s',
                    $queryString,
                    $base,
                    ldap_error($ds)
                );
            } elseif (ldap_count_entries($ds, $results) === 0) {
                if (in_array(
                    ldap_errno($ds),
                    array(static::LDAP_SIZELIMIT_EXCEEDED, static::LDAP_ADMINLIMIT_EXCEEDED)
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
                $count += 1;
                if (! $serverSorting || $offset === 0 || $offset < $count) {
                    $entries[ldap_get_dn($ds, $entry)] = $this->cleanupAttributes(
                        ldap_get_attributes($ds, $entry),
                        array_flip($fields)
                    );
                }
            } while (
                (! $serverSorting || $limit === 0 || $limit !== count($entries))
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
            ldap_search($ds, $base, $queryString); // Returns no entries, due to the page size
        } else {
            // Reset the paged search request so that subsequent requests succeed
            ldap_control_paged_result($ds, 0);
        }

        if (! $serverSorting && $query->hasOrder()) {
            uasort($entries, array($query, 'compare'));
            if ($limit && $count > $limit) {
                $entries = array_splice($entries, $query->hasOffset() ? $query->getOffset() : 0, $limit);
            }
        }

        return $entries;
    }

    /**
     * Clean up the given attributes and return them as simple object
     *
     * Applies column aliases, aggregates multi-value attributes as array and sets null for each missing attribute.
     *
     * @param   array   $attributes
     * @param   array   $requestedFields
     *
     * @return  object
     */
    public function cleanupAttributes($attributes, array $requestedFields)
    {
        // In case the result contains attributes with a differing case than the requested fields, it is
        // necessary to create another array to map attributes case insensitively to their requested counterparts.
        // This does also apply the virtual alias handling. (Since an LDAP server does not handle such)
        $loweredFieldMap = array();
        foreach ($requestedFields as $name => $alias) {
            $loweredFieldMap[strtolower($name)] = is_string($alias) ? $alias : $name;
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
            $cleanedAttributes[$requestedAttributeName] = $attribute_value;
        }

        // The result may not contain all requested fields, so populate the cleaned
        // result with the missing fields and their value being set to null
        foreach ($requestedFields as $name => $alias) {
            if (! is_string($alias)) {
                $alias = $name;
            }

            if (! array_key_exists($alias, $cleanedAttributes)) {
                $cleanedAttributes[$alias] = null;
                Logger::debug('LDAP query result does not provide the requested field "%s"', $name);
            }
        }

        return (object) $cleanedAttributes;
    }

    /**
     * Encode the given array of sort rules as ASN.1 octet stream according to RFC 2891
     *
     * @param   array   $sortRules
     *
     * @return  string
     * @throws ProgrammingError
     *
     * @todo    Produces an invalid stream, obviously
     */
    protected function encodeSortRules(array $sortRules)
    {
        if (count($sortRules) > 127) {
            throw new ProgrammingError(
                'Cannot encode more than 127 sort rules. Only length octets in short form are supported'
            );
        }

        $seq = '30' . str_pad(dechex(count($sortRules)), 2, '0', STR_PAD_LEFT);
        foreach ($sortRules as $rule) {
            $hexdAttribute = unpack('H*', $rule[0]);
            $seq .= '3002'
                . '04' . str_pad(dechex(strlen($rule[0])), 2, '0', STR_PAD_LEFT) . $hexdAttribute[1]
                . '0101' . ($rule[1] === Sortable::SORT_DESC ? 'ff' : '00');
        }

        return $seq;
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
            $hostname = 'ldaps://' . $hostname;
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
            'LDAP bind to %s:%s (%s / %s)',
            $this->hostname,
            $this->port,
            $this->bindDn,
            '***' /* $this->bindPw */
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
