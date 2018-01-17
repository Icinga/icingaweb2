<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use DateTime;
use Icinga\Data\ConfigObject;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\ProgrammingError;
use Icinga\Repository\LdapRepository;
use Icinga\Repository\RepositoryQuery;
use Icinga\Protocol\Ldap\LdapException;
use Icinga\User;

class LdapUserBackend extends LdapRepository implements UserBackendInterface, DomainAwareInterface, Inspectable
{
    /**
     * The base DN to use for a query
     *
     * @var string
     */
    protected $baseDn;

    /**
     * The objectClass where look for users
     *
     * @var string
     */
    protected $userClass;

    /**
     * The attribute name where to find a user's name
     *
     * @var string
     */
    protected $userNameAttribute;

    /**
     * The custom LDAP filter to apply on search queries
     *
     * @var string
     */
    protected $filter;

    /**
     * The domain the backend is responsible for
     *
     * @var string
     */
    protected $domain;

    /**
     * The columns which are not permitted to be queried
     *
     * @var array
     */
    protected $blacklistedQueryColumns = array('user');

    /**
     * The search columns being provided
     *
     * @var array
     */
    protected $searchColumns = array('user');

    /**
     * The default sort rules to be applied on a query
     *
     * @var array
     */
    protected $sortRules = array(
        'user_name' => array(
            'columns'   => array(
                'is_active desc',
                'user_name'
            )
        )
    );

    /**
     * Set the base DN to use for a query
     *
     * @param   string  $baseDn
     *
     * @return  $this
     */
    public function setBaseDn($baseDn)
    {
        if (($baseDn = trim($baseDn))) {
            $this->baseDn = $baseDn;
        }

        return $this;
    }

    /**
     * Return the base DN to use for a query
     *
     * @return  string
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }

    /**
     * Set the objectClass where to look for users
     *
     * @param   string  $userClass
     *
     * @return  $this
     */
    public function setUserClass($userClass)
    {
        $this->userClass = $this->getNormedAttribute($userClass);
        return $this;
    }

    /**
     * Return the objectClass where to look for users
     *
     * @return string
     */
    public function getUserClass()
    {
        return $this->userClass;
    }

    /**
     * Set the attribute name where to find a user's name
     *
     * @param   string  $userNameAttribute
     *
     * @return  $this
     */
    public function setUserNameAttribute($userNameAttribute)
    {
        $this->userNameAttribute = $this->getNormedAttribute($userNameAttribute);
        return $this;
    }

    /**
     * Return the attribute name where to find a user's name
     *
     * @return  string
     */
    public function getUserNameAttribute()
    {
        return $this->userNameAttribute;
    }

    /**
     * Set the custom LDAP filter to apply on search queries
     *
     * @param   string  $filter
     *
     * @return  $this
     */
    public function setFilter($filter)
    {
        if (($filter = trim($filter))) {
            if ($filter[0] === '(') {
                $filter = substr($filter, 1, -1);
            }

            $this->filter = $filter;
        }

        return $this;
    }

    /**
     * Return the custom LDAP filter to apply on search queries
     *
     * @return  string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set the domain the backend is responsible for
     *
     * @param   string  $domain
     *
     * @return  $this
     */
    public function setDomain($domain)
    {
        $domain = trim($domain);

        if (strlen($domain)) {
            $this->domain = $domain;
        }

        return $this;
    }

    /**
     * Initialize this repository's virtual tables
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $this->userClass has not been set yet
     */
    protected function initializeVirtualTables()
    {
        if ($this->userClass === null) {
            throw new ProgrammingError('It is required to set the object class where to find users first');
        }

        return array(
            'user' => $this->userClass
        );
    }

    /**
     * Initialize this repository's query columns
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $this->userNameAttribute has not been set yet
     */
    protected function initializeQueryColumns()
    {
        if ($this->userNameAttribute === null) {
            throw new ProgrammingError('It is required to set a attribute name where to find a user\'s name first');
        }

        if ($this->ds->getCapabilities()->isActiveDirectory()) {
            $isActiveAttribute = 'userAccountControl';
            $createdAtAttribute = 'whenCreated';
            $lastModifiedAttribute = 'whenChanged';
        } else {
            // TODO(jom): Elaborate whether it is possible to add dynamic support for the ppolicy
            $isActiveAttribute = 'shadowExpire';

            $createdAtAttribute = 'createTimestamp';
            $lastModifiedAttribute = 'modifyTimestamp';
        }

        return array(
            'user' => array(
                'user'          => $this->userNameAttribute,
                'user_name'     => $this->userNameAttribute,
                'is_active'     => $isActiveAttribute,
                'created_at'    => $createdAtAttribute,
                'last_modified' => $lastModifiedAttribute
            )
        );
    }

    /**
     * Initialize this repository's filter columns
     *
     * @return  array
     */
    protected function initializeFilterColumns()
    {
        return array(
            t('Username')       => 'user_name',
            t('Active')         => 'is_active',
            t('Created At')     => 'created_at',
            t('Last modified')  => 'last_modified'
        );
    }

    /**
     * Initialize this repository's conversion rules
     *
     * @return  array
     */
    protected function initializeConversionRules()
    {
        if ($this->ds->getCapabilities()->isActiveDirectory()) {
            $stateConverter = 'user_account_control';
        } else {
            $stateConverter = 'shadow_expire';
        }

        return array(
            'user' => array(
                'is_active'     => $stateConverter,
                'created_at'    => 'generalized_time',
                'last_modified' => 'generalized_time'
            )
        );
    }

    /**
     * Return whether the given userAccountControl value defines that a user is permitted to login
     *
     * @param   string|null     $value
     *
     * @return  bool
     */
    protected function retrieveUserAccountControl($value)
    {
        if ($value === null) {
            return $value;
        }

        $ADS_UF_ACCOUNTDISABLE = 2;
        return ((int) $value & $ADS_UF_ACCOUNTDISABLE) === 0;
    }

    /**
     * Return whether the given shadowExpire value defines that a user is permitted to login
     *
     * @param   string|null     $value
     *
     * @return  bool
     */
    protected function retrieveShadowExpire($value)
    {
        if ($value === null) {
            return $value;
        }

        $now = new DateTime();
        $bigBang = clone $now;
        $bigBang->setTimestamp(0);
        return ((int) $value) >= $bigBang->diff($now)->days;
    }

    /**
     * Validate that the requested table exists
     *
     * @param   string              $table      The table to validate
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *
     * @return  string
     *
     * @throws  ProgrammingError                In case the given table does not exist
     */
    public function requireTable($table, RepositoryQuery $query = null)
    {
        if ($query !== null) {
            $query->getQuery()->setBase($this->baseDn);
            if ($this->filter) {
                $query->getQuery()->setNativeFilter($this->filter);
            }
        }

        return parent::requireTable($table, $query);
    }

    /**
     * Validate that the given column is a valid query target and return it or the actual name if it's an alias
     *
     * @param   string              $table  The table where to look for the column or alias
     * @param   string              $name   The name or alias of the column to validate
     * @param   RepositoryQuery     $query  An optional query to pass as context
     *
     * @return  string                      The given column's name
     *
     * @throws  QueryException              In case the given column is not a valid query column
     */
    public function requireQueryColumn($table, $name, RepositoryQuery $query = null)
    {
        $column = parent::requireQueryColumn($table, $name, $query);
        if ($name === 'user_name' && $query !== null) {
            $query->getQuery()->setUnfoldAttribute('user_name');
        }

        return $column;
    }

    /**
     * Authenticate the given user
     *
     * @param   User        $user
     * @param   string      $password
     *
     * @return  bool                        True on success, false on failure
     *
     * @throws  AuthenticationException     In case authentication is not possible due to an error
     */
    public function authenticate(User $user, $password)
    {
        if ($this->domain !== null) {
            if (! $user->hasDomain() || strtolower($user->getDomain()) !== strtolower($this->domain)) {
                return false;
            }

            $username = $user->getLocalUsername();
        } else {
            $username = $user->getUsername();
        }

        try {
            $userDn = $this
                ->select()
                ->where('user_name', str_replace('*', '', $username))
                ->getQuery()
                ->setUsePagedResults(false)
                ->fetchDn();
            if ($userDn === null) {
                return false;
            }

            $validCredentials = $this->ds->testCredentials($userDn, $password);
            if ($validCredentials) {
                $user->setAdditional('ldap_dn', $userDn);
            }

            return $validCredentials;
        } catch (LdapException $e) {
            throw new AuthenticationException(
                'Failed to authenticate user "%s" against backend "%s". An exception was thrown:',
                $username,
                $this->getName(),
                $e
            );
        }
    }

    /**
     * Inspect if this LDAP User Backend is working as expected by probing the backend
     * and testing if thea uthentication is possible
     *
     * Try to bind to the backend and fetch a single user to check if:
     * <ul>
     *  <li>Connection credentials are correct and the bind is possible</li>
     *  <li>At least one user exists</li>
     *  <li>The specified userClass has the property specified by userNameAttribute</li>
     * </ul>
     *
     * @return  Inspection  Inspection result
     */
    public function inspect()
    {
        $result = new Inspection('Ldap User Backend');

        // inspect the used connection to get more diagnostic info in case the connection is not working
        $result->write($this->ds->inspect());
        try {
            try {
                $res = $this->select()->fetchRow();
            } catch (LdapException $e) {
                throw new AuthenticationException('Connection not possible', $e);
            }
            $result->write('Searching for: ' . sprintf(
                'objectClass "%s" in DN "%s" (Filter: %s)',
                $this->userClass,
                $this->baseDn ?: $this->ds->getDn(),
                $this->filter ?: 'None'
            ));
            if ($res === false) {
                throw new AuthenticationException('Error, no users found in backend');
            }
            $result->write(sprintf('%d users found in backend', $this->select()->count()));
            if (! isset($res->user_name)) {
                throw new AuthenticationException(
                    'UserNameAttribute "%s" not existing in objectClass "%s"',
                    $this->userNameAttribute,
                    $this->userClass
                );
            }
        } catch (AuthenticationException $e) {
            if (($previous = $e->getPrevious()) !== null) {
                $result->error($previous->getMessage());
            } else {
                $result->error($e->getMessage());
            }
        } catch (Exception $e) {
            $result->error(sprintf('Unable to validate authentication: %s', $e->getMessage()));
        }
        return $result;
    }
}
