<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\Data\ConfigObject;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\ProgrammingError;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryQuery;
use Icinga\Protocol\Ldap\Exception as LdapException;
use Icinga\Protocol\Ldap\Expression;
use Icinga\User;

class LdapUserBackend extends Repository implements UserBackendInterface
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
     * The columns which are not permitted to be queried
     *
     * @var array
     */
    protected $filterColumns = array('user');

    /**
     * The default sort rules to be applied on a query
     *
     * @var array
     */
    protected $sortRules = array(
        'user_name' => array(
            'columns'   => array(
                'user_name asc',
                'is_active desc'
            )
        )
    );

    protected $groupOptions;

    /**
     * Normed attribute names based on known LDAP environments
     *
     * @var array
     */
    protected $normedAttributes = array(
        'uid'               => 'uid',
        'user'              => 'user',
        'inetorgperson'     => 'inetOrgPerson',
        'samaccountname'    => 'sAMAccountName'
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
     * Sets also the base table name for the underlying repository.
     *
     * @param   string  $userClass
     *
     * @return  $this
     */
    public function setUserClass($userClass)
    {
        $this->baseTable = $this->userClass = $this->getNormedAttribute($userClass);
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

    public function setGroupOptions(array $options)
    {
        $this->groupOptions = $options;
        return $this;
    }

    public function getGroupOptions()
    {
        return $this->groupOptions;
    }

    /**
     * Return the given attribute name normed to known LDAP enviroments, if possible
     *
     * @param   string  $name
     *
     * @return  string
     */
    protected function getNormedAttribute($name)
    {
        $loweredName = strtolower($name);
        if (array_key_exists($loweredName, $this->normedAttributes)) {
            return $this->normedAttributes[$loweredName];
        }

        return $name;
    }

    /**
     * Apply the given configuration to this backend
     *
     * @param   ConfigObject    $config
     *
     * @return  $this
     */
    public function setConfig(ConfigObject $config)
    {
        return $this
            ->setBaseDn($config->base_dn)
            ->setUserClass($config->user_class)
            ->setUserNameAttribute($config->user_name_attribute)
            ->setFilter($config->filter);
    }

    /**
     * Return a new query for the given columns
     *
     * @param   array   $columns    The desired columns, if null all columns will be queried
     *
     * @return  RepositoryQuery
     */
    public function select(array $columns = null)
    {
        $this->initializeQueryColumns();

        $query = parent::select($columns);
        $query->getQuery()->setBase($this->baseDn);
        if ($this->filter) {
            $query->getQuery()->where(new Expression($this->filter));
        }

        return $query;
    }

    /**
     * Initialize this repository's query columns
     *
     * @throws  ProgrammingError    In case either $this->userNameAttribute or $this->userClass has not been set yet
     */
    protected function initializeQueryColumns()
    {
        if ($this->queryColumns === null) {
            if ($this->userClass === null) {
                throw new ProgrammingError('It is required to set the objectClass where to look for users first');
            }
            if ($this->userNameAttribute === null) {
                throw new ProgrammingError('It is required to set a attribute name where to find a user\'s name first');
            }

            $this->queryColumns[$this->userClass] = array(
                'user'          => $this->userNameAttribute,
                'user_name'     => $this->userNameAttribute,
                'is_active'     => 'unknown', // msExchUserAccountControl == 2/512/514? <- AD LDAP
                'created_at'    => 'whenCreated', // That's AD LDAP,
                'last_modified' => 'whenChanged' // what's OpenLDAP?
            );
        }
    }

    /**
     * Probe the backend to test if authentication is possible
     *
     * Try to bind to the backend and fetch a single user to check if:
     * <ul>
     *  <li>Connection credentials are correct and the bind is possible</li>
     *  <li>At least one user exists</li>
     *  <li>The specified userClass has the property specified by userNameAttribute</li>
     * </ul>
     *
     * @throws  AuthenticationException     When authentication is not possible
     */
    public function assertAuthenticationPossible()
    {
        try {
            $result = $this->select()->fetchRow();
        } catch (LdapException $e) {
            throw new AuthenticationException('Connection not possible.', $e);
        }

        if ($result === null) {
            throw new AuthenticationException(
                'No objects with objectClass "%s" in DN "%s" found. (Filter: %s)',
                $this->userClass,
                $this->baseDn ?: $this->ds->getDn(),
                $this->filter ?: 'None'
            );
        }

        if (! isset($result->user_name)) {
            throw new AuthenticationException(
                'UserNameAttribute "%s" not existing in objectClass "%s"',
                $this->userNameAttribute,
                $this->userClass
            );
        }
    }

    /**
     * Retrieve the user groups
     *
     * @TODO: Subject to change, see #7343
     *
     * @param string $dn
     *
     * @return array
     */
    public function getGroups($dn)
    {
        if (empty($this->groupOptions) || ! isset($this->groupOptions['group_base_dn'])) {
            return array();
        }

        $result = $this->ds->select()
            ->setBase($this->groupOptions['group_base_dn'])
            ->from(
                $this->groupOptions['group_class'],
                array($this->groupOptions['group_attribute'])
            )
            ->where(
                $this->groupOptions['group_member_attribute'],
                $dn
            )
            ->fetchAll();

        $groups = array();
        foreach ($result as $group) {
            $groups[] = $group->{$this->groupOptions['group_attribute']};
        }

        return $groups;
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
        try {
            $userDn = $this
                ->select()
                ->where('user_name', str_replace('*', '', $user->getUsername()))
                ->getQuery()
                ->setUsePagedResults(false)
                ->fetchDn();

            if ($userDn === null) {
                return false;
            }

            $authenticated = $this->ds->testCredentials($userDn, $password);
            if ($authenticated) {
                $groups = $this->getGroups($userDn);
                if ($groups !== null) {
                    $user->setGroups($groups);
                }
            }

            return $authenticated;
        } catch (LdapException $e) {
            throw new AuthenticationException(
                'Failed to authenticate user "%s" against backend "%s". An exception was thrown:',
                $user->getUsername(),
                $this->getName(),
                $e
            );
        }
    }
}
