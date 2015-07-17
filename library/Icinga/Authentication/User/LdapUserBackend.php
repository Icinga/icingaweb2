<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
use Icinga\Protocol\Ldap\Expression;
use Icinga\User;

class LdapUserBackend extends LdapRepository implements UserBackendInterface, Inspectable
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
     * @return  array
     *
     * @throws  ProgrammingError    In case either $this->userNameAttribute or $this->userClass has not been set yet
     */
    protected function initializeQueryColumns()
    {
        if ($this->userClass === null) {
            throw new ProgrammingError('It is required to set the objectClass where to look for users first');
        }
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
            $this->userClass => array(
                'user'          => $this->userNameAttribute,
                'user_name'     => $this->userNameAttribute,
                'is_active'     => $isActiveAttribute,
                'created_at'    => $createdAtAttribute,
                'last_modified' => $lastModifiedAttribute
            )
        );
    }

    /**
     * Initialize this repository's conversion rules
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $this->userClass has not been set yet
     */
    protected function initializeConversionRules()
    {
        if ($this->userClass === null) {
            throw new ProgrammingError('It is required to set the objectClass where to look for users first');
        }

        if ($this->ds->getCapabilities()->isActiveDirectory()) {
            $stateConverter = 'user_account_control';
        } else {
            $stateConverter = 'shadow_expire';
        }

        return array(
            $this->userClass => array(
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

     * @param   Inspection  $info           Optional inspection to fill with diagnostic info
     *
     * @throws  AuthenticationException     When authentication is not possible
     */
    public function assertAuthenticationPossible(Inspection $insp = null)
    {

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

            return $this->ds->testCredentials($userDn, $password);
        } catch (LdapException $e) {
            throw new AuthenticationException(
                'Failed to authenticate user "%s" against backend "%s". An exception was thrown:',
                $user->getUsername(),
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
