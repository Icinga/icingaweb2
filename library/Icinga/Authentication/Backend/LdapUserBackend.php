<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\Backend;

use Icinga\User;
use Icinga\Authentication\UserBackend;
use Icinga\Protocol\Ldap\Query;
use Icinga\Protocol\Ldap\Connection;
use Icinga\Exception\AuthenticationException;
use Icinga\Protocol\Ldap\Exception as LdapException;
use Icinga\Protocol\Ldap\Expression;

class LdapUserBackend extends UserBackend
{
    /**
     * Connection to the LDAP server
     *
     * @var Connection
     */
    protected $conn;

    protected $baseDn;

    protected $userClass;

    protected $userNameAttribute;

    protected $customFilter;

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

    public function __construct(
        Connection $conn,
        $userClass,
        $userNameAttribute,
        $baseDn,
        $cutomFilter,
        $groupOptions = null
    ) {
        $this->conn = $conn;
        $this->baseDn = trim($baseDn) ?: $conn->getDN();
        $this->userClass = $this->getNormedAttribute($userClass);
        $this->userNameAttribute = $this->getNormedAttribute($userNameAttribute);
        $this->customFilter = trim($cutomFilter);
        $this->groupOptions = $groupOptions;
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
     * Create a query to select all usernames
     *
     * @return  Query
     */
    protected function selectUsers()
    {
        $query = $this->conn->select()->setBase($this->baseDn)->from(
            $this->userClass,
            array(
                $this->userNameAttribute
            )
        );

        if ($this->customFilter) {
            $query->addFilter(new Expression($this->customFilter));
        }

        return $query;
    }

    /**
     * Create a query filtered by the given username
     *
     * @param   string  $username
     *
     * @return  Query
     */
    protected function selectUser($username)
    {
        return $this->selectUsers()->setUsePagedResults(false)->where(
            $this->userNameAttribute,
            str_replace('*', '', $username)
        );
    }

    /**
     * Probe the backend to test if authentication is possible
     *
     * Try to bind to the backend and query all available users to check if:
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
            $result = $this->selectUsers()->fetchRow();
        } catch (LdapException $e) {
            throw new AuthenticationException('Connection not possible.', $e);
        }

        if ($result === null) {
            throw new AuthenticationException(
                'No objects with objectClass="%s" in DN="%s" found. (Filter: %s)',
                $this->userClass,
                $this->baseDn,
                $this->customFilter ?: 'None'
            );
        }

        if (! isset($result->{$this->userNameAttribute})) {
            throw new AuthenticationException(
                'UserNameAttribute "%s" not existing in objectClass="%s"',
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

        $q = $this->conn->select()
            ->setBase($this->groupOptions['group_base_dn'])
            ->from(
                $this->groupOptions['group_class'],
                array($this->groupOptions['group_attribute'])
            )
            ->where(
                $this->groupOptions['group_member_attribute'],
                $dn
            );

        $result = $this->conn->fetchAll($q);

        $groups = array();

        foreach ($result as $group) {
            $groups[] = $group->{$this->groupOptions['group_attribute']};
        }

        return $groups;
    }

    /**
     * Return whether the given user exists
     *
     * @param   User    $user
     *
     * @return  bool
     */
    public function hasUser(User $user)
    {
        $username = $user->getUsername();
        $entry = $this->selectUser($username)->fetchOne();

        if (is_array($entry)) {
            return in_array(strtolower($username), array_map('strtolower', $entry));
        }

        return strtolower($entry) === strtolower($username);
    }

    /**
     * Return whether the given user credentials are valid
     *
     * @param   User    $user
     * @param   string  $password
     * @param   boolean $healthCheck        Assert that authentication is possible at all
     *
     * @return  bool
     *
     * @throws  AuthenticationException     In case an error occured or the health check has failed
     */
    public function authenticate(User $user, $password, $healthCheck = false)
    {
        if ($healthCheck) {
            try {
                $this->assertAuthenticationPossible();
            } catch (AuthenticationException $e) {
                throw new AuthenticationException(
                    'Authentication against backend "%s" not possible.',
                    $this->getName(),
                    $e
                );
            }
        }

        if (! $this->hasUser($user)) {
            return false;
        }

        try {
            $userDn = $this->conn->fetchDN($this->selectUser($user->getUsername()));
            $authenticated = $this->conn->testCredentials(
                $userDn,
                $password
            );

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

    /**
     * Get the number of users available
     *
     * @return int
     */
    public function count()
    {
        return $this->selectUsers()->count();
    }

    /**
     * Return the names of all available users
     *
     * @return  array
     */
    public function listUsers()
    {
        $users = array();
        foreach ($this->selectUsers()->fetchAll() as $row) {
            if (is_array($row->{$this->userNameAttribute})) {
                foreach ($row->{$this->userNameAttribute} as $col) {
                    $users[] = $col;
                }
            } else {
                $users[] = $row->{$this->userNameAttribute};
            }
        }

        return $users;
    }
}
