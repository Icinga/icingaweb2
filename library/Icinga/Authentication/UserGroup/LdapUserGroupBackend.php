<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Exception\ProgrammingError;
use Icinga\Repository\LdapRepository;
use Icinga\Repository\RepositoryQuery;
use Icinga\User;

class LdapUserGroupBackend /*extends LdapRepository*/ implements UserGroupBackendInterface
{
    /**
     * The base DN to use for a user query
     *
     * @var string
     */
    protected $userBaseDn;

    /**
     * The base DN to use for a group query
     *
     * @var string
     */
    protected $groupBaseDn;

    /**
     * The objectClass where look for users
     *
     * @var string
     */
    protected $userClass;

    /**
     * The objectClass where look for groups
     *
     * @var string
     */
    protected $groupClass;

    /**
     * The attribute name where to find a user's name
     *
     * @var string
     */
    protected $userNameAttribute;

    /**
     * The attribute name where to find a group's name
     *
     * @var string
     */
    protected $groupNameAttribute;

    /**
     * The attribute name where to find a group's member
     *
     * @var string
     */
    protected $groupMemberAttribute;

    /**
     * The columns which are not permitted to be queried
     *
     * @var array
     */
    protected $filterColumns = array('group', 'user');

    /**
     * The default sort rules to be applied on a query
     *
     * @var array
     */
    protected $sortRules = array(
        'group_name' => array(
            'order' => 'asc'
        )
    );

    /**
     * Normed attribute names based on known LDAP environments
     *
     * @var array
     */
    protected $normedAttributes = array(
        'uid'               => 'uid',
        'gid'               => 'gid',
        'user'              => 'user',
        'group'             => 'group',
        'member'            => 'member',
        'inetorgperson'     => 'inetOrgPerson',
        'samaccountname'    => 'sAMAccountName'
    );

    /**
     * The name of this repository
     *
     * @var string
     */
    protected $name;

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
     * Set this repository's name
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return this repository's name
     *
     * In case no name has been explicitly set yet, the class name is returned.
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the base DN to use for a user query
     *
     * @param   string  $baseDn
     *
     * @return  $this
     */
    public function setUserBaseDn($baseDn)
    {
        if (($baseDn = trim($baseDn))) {
            $this->userBaseDn = $baseDn;
        }

        return $this;
    }

    /**
     * Return the base DN to use for a user query
     *
     * @return  string
     */
    public function getUserBaseDn()
    {
        return $this->userBaseDn;
    }

    /**
     * Set the base DN to use for a group query
     *
     * @param   string  $baseDn
     *
     * @return  $this
     */
    public function setGroupBaseDn($baseDn)
    {
        if (($baseDn = trim($baseDn))) {
            $this->groupBaseDn = $baseDn;
        }

        return $this;
    }

    /**
     * Return the base DN to use for a group query
     *
     * @return  string
     */
    public function getGroupBaseDn()
    {
        return $this->groupBaseDn;
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
     * Set the objectClass where to look for groups
     *
     * Sets also the base table name for the underlying repository.
     *
     * @param   string  $groupClass
     *
     * @return  $this
     */
    public function setGroupClass($groupClass)
    {
        $this->baseTable = $this->groupClass = $this->getNormedAttribute($groupClass);
        return $this;
    }

    /**
     * Return the objectClass where to look for groups
     *
     * @return string
     */
    public function getGroupClass()
    {
        return $this->groupClass;
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
     * Set the attribute name where to find a group's name
     *
     * @param   string  $groupNameAttribute
     *
     * @return  $this
     */
    public function setGroupNameAttribute($groupNameAttribute)
    {
        $this->groupNameAttribute = $this->getNormedAttribute($groupNameAttribute);
        return $this;
    }

    /**
     * Return the attribute name where to find a group's name
     *
     * @return  string
     */
    public function getGroupNameAttribute()
    {
        return $this->groupNameAttribute;
    }

    /**
     * Set the attribute name where to find a group's member
     *
     * @param   string  $groupMemberAttribute
     *
     * @return  $this
     */
    public function setGroupMemberAttribute($groupMemberAttribute)
    {
        $this->groupMemberAttribute = $this->getNormedAttribute($groupMemberAttribute);
        return $this;
    }

    /**
     * Return the attribute name where to find a group's member
     *
     * @return  string
     */
    public function getGroupMemberAttribute()
    {
        return $this->groupMemberAttribute;
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
        $query->getQuery()->setBase($this->groupBaseDn);
        return $query;
    }

    /**
     * Initialize this repository's query columns
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case either $this->groupNameAttribute or $this->groupClass has not been set yet
     */
    protected function initializeQueryColumns()
    {
        if ($this->groupClass === null) {
            throw new ProgrammingError('It is required to set the objectClass where to look for groups first');
        }
        if ($this->groupNameAttribute === null) {
            throw new ProgrammingError('It is required to set a attribute name where to find a group\'s name first');
        }

        if ($this->ds->getCapabilities()->hasAdOid()) {
            $createdAtAttribute = 'whenCreated';
            $lastModifiedAttribute = 'whenChanged';
        } else {
            $createdAtAttribute = 'createTimestamp';
            $lastModifiedAttribute = 'modifyTimestamp';
        }

        // TODO(jom): Fetching memberships does not work currently, we'll need some aggregate functionality!
        $columns = array(
            'group'         => $this->groupNameAttribute,
            'group_name'    => $this->groupNameAttribute,
            'user'          => $this->groupMemberAttribute,
            'user_name'     => $this->groupMemberAttribute,
            'created_at'    => $createdAtAttribute,
            'last_modified' => $lastModifiedAttribute
        );
        return array('group' => $columns, 'group_membership' => $columns);
    }

    /**
     * Initialize this repository's conversion rules
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $this->groupClass has not been set yet
     */
    protected function initializeConversionRules()
    {
        if ($this->groupClass === null) {
            throw new ProgrammingError('It is required to set the objectClass where to look for groups first');
        }

        return array(
            $this->groupClass => array(
                'created_at'    => 'generalized_time',
                'last_modified' => 'generalized_time'
            )
        );
    }

    /**
     * Validate that the requested table exists
     *
     * This will return $this->groupClass in case $table equals "group" or "group_membership".
     *
     * @param   string              $table      The table to validate
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *                                          (unused by the base implementation)
     *
     * @return  string
     *
     * @throws  ProgrammingError                In case the given table does not exist
     */
    public function requireTable($table, RepositoryQuery $query = null)
    {
        $table = parent::requireTable($table, $query);
        if ($table === 'group' || $table === 'group_membership') {
            $table = $this->groupClass;
        }

        return $table;
    }

    /**
     * Return the groups the given user is a member of
     *
     * @param   User    $user
     *
     * @return  array
     */
    public function getMemberships(User $user)
    {
        $userDn = $this->ds
            ->select()
            ->from($this->userClass)
            ->where($this->userNameAttribute, $user->getUsername())
            ->setBase($this->userBaseDn)
            ->setUsePagedResults(false)
            ->fetchDn();

        if ($userDn === null) {
            return array();
        }

        $groupQuery = $this->ds
            ->select()
            ->from($this->groupClass, array($this->groupNameAttribute))
            ->where($this->groupMemberAttribute, $userDn)
            ->setBase($this->groupBaseDn);

        $groups = array();
        foreach ($groupQuery as $row) {
            $groups[] = $row->{$this->groupNameAttribute};
        }

        return $groups;
    }
}
