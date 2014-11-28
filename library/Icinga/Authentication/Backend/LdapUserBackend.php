<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\User;
use Icinga\Authentication\UserBackend;
use Icinga\Protocol\Ldap\Connection;
use Icinga\Exception\AuthenticationException;
use Icinga\Protocol\Ldap\Exception as LdapException;

class LdapUserBackend extends UserBackend
{
    /**
     * Connection to the LDAP server
     *
     * @var Connection
     **/
    protected $conn;

    protected $baseDn;

    protected $userClass;

    protected $userNameAttribute;

    protected $groupOptions;

    public function __construct(Connection $conn, $userClass, $userNameAttribute, $baseDn, $groupOptions = null)
    {
        $this->conn = $conn;
        $this->baseDn = trim($baseDn) !== '' ? $baseDn : $conn->getDN();
        $this->userClass = $userClass;
        $this->userNameAttribute = $userNameAttribute;
        $this->groupOptions = $groupOptions;
    }

    /**
     * @return \Icinga\Protocol\Ldap\Query
     */
    protected function selectUsers()
    {
        return $this->conn->select()->setBase($this->baseDn)->from(
            $this->userClass,
            array(
                $this->userNameAttribute
            )
        );
    }

    /**
     * Create query
     *
     * @param   string $username
     *
     * @return  \Icinga\Protocol\Ldap\Query
     **/
    protected function selectUser($username)
    {
        return $this->selectUsers()->where(
                $this->userNameAttribute,
                str_replace('*', '', $username)
            );
    }

    /**
     * Probe the backend to test if authentication is possible
     *
     * Try to bind to the backend and query all available users to check if:
     * <ul>
     *  <li>User connection credentials are correct and the bind is possible</li>
     *  <li>At least one user exists</li>
     *  <li>The specified userClass has the property specified by userNameAttribute</li>
     * </ul>
     *
     * @throws AuthenticationException  When authentication is not possible
     */
    public function assertAuthenticationPossible()
    {
        try {
            $q = $this->conn->select()->setBase($this->baseDn)->from($this->userClass);
            $result = $q->fetchRow();
        } catch (LdapException $e) {
            throw new AuthenticationException('Connection not possible.', $e);
        }

        if (! isset($result)) {
            throw new AuthenticationException(
                'No objects with objectClass="%s" in DN="%s" found.',
                $this->userClass,
                $this->baseDn
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
     * Test whether the given user exists
     *
     * @param   User $user
     *
     * @return  bool
     * @throws  AuthenticationException
     */
    public function hasUser(User $user)
    {
        $username = $user->getUsername();
        return strtolower($this->conn->fetchOne($this->selectUser($username))) === strtolower($username);
    }

    /**
     * Authenticate the given user and return true on success, false on failure and null on error
     *
     * @param   User    $user
     * @param   string  $password
     * @param   boolean $healthCheck        Perform additional health checks to generate more useful exceptions in case
     *                                      of a configuration or backend error
     *
     * @return  bool                        True when the authentication was successful, false when the username
     *                                      or password was invalid
     * @throws  AuthenticationException     When an error occurred during authentication and authentication is not possible
     */
    public function authenticate(User $user, $password, $healthCheck = true)
    {
        if ($healthCheck) {
            try {
                $this->assertAuthenticationPossible();
            } catch (AuthenticationException $e) {
                // Authentication not possible
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
            // Error during authentication of this specific user
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
        return $this->conn->count($this->selectUsers());
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
            $users[] = $row->{$this->userNameAttribute};
        }
        return $users;
    }
}
