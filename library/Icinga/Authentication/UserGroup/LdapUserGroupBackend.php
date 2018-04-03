<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Exception;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\LdapUserBackend;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;
use Icinga\Protocol\Ldap\LdapException;
use Icinga\Protocol\Ldap\LdapUtils;
use Icinga\Repository\LdapRepository;
use Icinga\Repository\RepositoryQuery;
use Icinga\User;

class LdapUserGroupBackend extends LdapRepository implements Inspectable, UserGroupBackendInterface
{
    /**
     * The user backend being associated with this user group backend
     *
     * @var LdapUserBackend
     */
    protected $userBackend;

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
     * Whether the attribute name where to find a group's member holds ambiguous values
     *
     * @var bool
     */
    protected $ambiguousMemberAttribute;

    /**
     * The custom LDAP filter to apply on a user query
     *
     * @var string
     */
    protected $userFilter;

    /**
     * The custom LDAP filter to apply on a group query
     *
     * @var string
     */
    protected $groupFilter;

    /**
     * ActiveDirectory nested group on the user?
     *
     * @var bool
     */
    protected $nestedGroupSearch;

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
    protected $blacklistedQueryColumns = array('group', 'user');

    /**
     * The search columns being provided
     *
     * @var array
     */
    protected $searchColumns = array('group', 'user');

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
     * Set the user backend to be associated with this user group backend
     *
     * @param   LdapUserBackend     $backend
     *
     * @return  $this
     */
    public function setUserBackend(LdapUserBackend $backend)
    {
        $this->userBackend = $backend;
        return $this;
    }

    /**
     * Return the user backend being associated with this user group backend
     *
     * @return  LdapUserBackend
     */
    public function getUserBackend()
    {
        return $this->userBackend;
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
     * @param   string  $groupClass
     *
     * @return  $this
     */
    public function setGroupClass($groupClass)
    {
        $this->groupClass = $this->getNormedAttribute($groupClass);
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
     * Set the custom LDAP filter to apply on a user query
     *
     * @param   string  $filter
     *
     * @return  $this
     */
    public function setUserFilter($filter)
    {
        if (($filter = trim($filter))) {
            if ($filter[0] === '(') {
                $filter = substr($filter, 1, -1);
            }

            $this->userFilter = $filter;
        }

        return $this;
    }

    /**
     * Return the custom LDAP filter to apply on a user query
     *
     * @return  string
     */
    public function getUserFilter()
    {
        return $this->userFilter;
    }

    /**
     * Set the custom LDAP filter to apply on a group query
     *
     * @param   string  $filter
     *
     * @return  $this
     */
    public function setGroupFilter($filter)
    {
        if (($filter = trim($filter))) {
            $this->groupFilter = $filter;
        }

        return $this;
    }

    /**
     * Return the custom LDAP filter to apply on a group query
     *
     * @return  string
     */
    public function getGroupFilter()
    {
        return $this->groupFilter;
    }

    /**
     * Set nestedGroupSearch for the group query
     *
     * @param   bool    $enable
     *
     * @return  $this
     */
    public function setNestedGroupSearch($enable = true)
    {
        $this->nestedGroupSearch = $enable;
        return $this;
    }

    /**
     * Get nestedGroupSearch for the group query
     *
     * @return bool
     */
    public function getNestedGroupSearch()
    {
        return $this->nestedGroupSearch;
    }

    /**
     * Get the domain the backend is responsible for
     *
     * If the LDAP group backend is linked with a LDAP user backend,
     * the domain of the user backend will be returned.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->userBackend !== null ? $this->userBackend->getDomain() : $this->domain;
    }

    /**
     * Set the domain the backend is responsible for
     *
     * If the LDAP group backend is linked with a LDAP user backend,
     * the domain of the user backend will be used nonetheless.
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
     * Return whether the attribute name where to find a group's member holds ambiguous values
     *
     * This tries to detect if the member attribute of groups contain:
     *
     *  full DN -> distinguished name of another object
     *  other   -> ambiguous field referencing the member by userNameAttribute
     *
     * @return  bool
     *
     * @throws  ProgrammingError    In case either $this->groupClass or $this->groupMemberAttribute
     *                              has not been set yet
     */
    protected function isMemberAttributeAmbiguous()
    {
        if ($this->ambiguousMemberAttribute === null) {
            if ($this->groupClass === null) {
                throw new ProgrammingError(
                    'It is required to set the objectClass where to look for groups first'
                );
            } elseif ($this->groupMemberAttribute === null) {
                throw new ProgrammingError(
                    'It is required to set a attribute name where to find a group\'s members first'
                );
            }

            $sampleValue = $this->ds
                ->select()
                ->from($this->groupClass, array($this->groupMemberAttribute))
                ->where($this->groupMemberAttribute, '*')
                ->setUnfoldAttribute($this->groupMemberAttribute)
                ->setBase($this->groupBaseDn)
                ->fetchOne();

            $this->ambiguousMemberAttribute = ! LdapUtils::isDn($sampleValue);
        }

        return $this->ambiguousMemberAttribute;
    }

    /**
     * Initialize this repository's virtual tables
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $this->groupClass has not been set yet
     */
    protected function initializeVirtualTables()
    {
        if ($this->groupClass === null) {
            throw new ProgrammingError('It is required to set the object class where to find groups first');
        }

        return array(
            'group'             => $this->groupClass,
            'group_membership'  => $this->groupClass
        );
    }

    /**
     * Initialize this repository's query columns
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case either $this->groupNameAttribute or
     *                              $this->groupMemberAttribute has not been set yet
     */
    protected function initializeQueryColumns()
    {
        if ($this->groupNameAttribute === null) {
            throw new ProgrammingError('It is required to set a attribute name where to find a group\'s name first');
        }
        if ($this->groupMemberAttribute === null) {
            throw new ProgrammingError('It is required to set a attribute name where to find a group\'s members first');
        }

        if ($this->ds->getCapabilities()->isActiveDirectory()) {
            $createdAtAttribute = 'whenCreated';
            $lastModifiedAttribute = 'whenChanged';
        } else {
            $createdAtAttribute = 'createTimestamp';
            $lastModifiedAttribute = 'modifyTimestamp';
        }

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
     * Initialize this repository's filter columns
     *
     * @return  array
     */
    protected function initializeFilterColumns()
    {
        return array(
            t('Username')       => 'user_name',
            t('User Group')     => 'group_name',
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
        $rules = array(
            'group' => array(
                'created_at'    => 'generalized_time',
                'last_modified' => 'generalized_time'
            ),
            'group_membership' => array(
                'created_at'    => 'generalized_time',
                'last_modified' => 'generalized_time'
            )
        );
        if (! $this->isMemberAttributeAmbiguous()) {
            $rules['group_membership']['user_name'] = 'user_name';
            $rules['group_membership']['user'] = 'user_name';
            $rules['group']['user_name'] = 'user_name';
            $rules['group']['user'] = 'user_name';
        }

        return $rules;
    }

    /**
     * Return the distinguished name for the given uid or gid
     *
     * @param   string  $name
     *
     * @return  string
     */
    protected function persistUserName($name)
    {
        try {
            $userDn = $this->ds
                ->select()
                ->from($this->userClass, array())
                ->where($this->userNameAttribute, $name)
                ->setBase($this->userBaseDn)
                ->setUsePagedResults(false)
                ->fetchDn();
            if ($userDn) {
                return $userDn;
            }

            $groupDn = $this->ds
                ->select()
                ->from($this->groupClass, array())
                ->where($this->groupNameAttribute, $name)
                ->setBase($this->groupBaseDn)
                ->setUsePagedResults(false)
                ->fetchDn();
            if ($groupDn) {
                return $groupDn;
            }
        } catch (LdapException $_) {
            // pass
        }

        Logger::debug('Unable to persist uid or gid "%s" in repository "%s". No DN found.', $name, $this->getName());
        return $name;
    }

    /**
     * Return the uid for the given distinguished name
     *
     * @param   string  $username
     *
     * @param   string
     */
    protected function retrieveUserName($dn)
    {
        return $this->ds
            ->select()
            ->from('*', array($this->userNameAttribute))
            ->setBase($dn)
            ->fetchOne();
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
            $query->getQuery()->setBase($this->groupBaseDn);
            if ($table === 'group' && $this->groupFilter) {
                $query->getQuery()->setNativeFilter($this->groupFilter);
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
     * Return the groups the given user is a member of
     *
     * @param   User    $user
     *
     * @return  array
     */
    public function getMemberships(User $user)
    {
        $domain = $this->getDomain();

        if ($domain !== null) {
            if (! $user->hasDomain() || strtolower($user->getDomain()) !== strtolower($domain)) {
                return array();
            }

            $username = $user->getLocalUsername();
        } else {
            $username = $user->getUsername();
        }

        if ($this->isMemberAttributeAmbiguous()) {
            $queryValue = $username;
        } elseif (($queryValue = $user->getAdditional('ldap_dn')) === null) {
            $userQuery = $this->ds
                ->select()
                ->from($this->userClass)
                ->where($this->userNameAttribute, $username)
                ->setBase($this->userBaseDn)
                ->setUsePagedResults(false);
            if ($this->userFilter) {
                $userQuery->setNativeFilter($this->userFilter);
            }

            if (($queryValue = $userQuery->fetchDn()) === null) {
                return array();
            }
        }

        if ($this->nestedGroupSearch) {
            $groupMemberAttribute = $this->groupMemberAttribute . ':1.2.840.113556.1.4.1941:';
        } else {
            $groupMemberAttribute = $this->groupMemberAttribute;
        }

        $groupQuery = $this->ds
            ->select()
            ->from($this->groupClass, array($this->groupNameAttribute))
            ->where($groupMemberAttribute, $queryValue)
            ->setBase($this->groupBaseDn);
        if ($this->groupFilter) {
            $groupQuery->setNativeFilter($this->groupFilter);
        }

        $groups = array();
        foreach ($groupQuery as $row) {
            $groups[] = $row->{$this->groupNameAttribute};
            if ($domain !== null) {
                $groups[] = $row->{$this->groupNameAttribute} . "@$domain";
            }
        }

        return $groups;
    }

    /**
     * Return the name of the backend that is providing the given user
     *
     * @param   string  $username   Unused
     *
     * @return  null|string     The name of the backend or null in case this information is not available
     */
    public function getUserBackendName($username)
    {
        $userBackend = $this->getUserBackend();
        if ($userBackend !== null) {
            return $userBackend->getName();
        }
    }

    /**
     * Apply the given configuration on this backend
     *
     * @param   ConfigObject    $config
     *
     * @return  $this
     *
     * @throws  ConfigurationError      In case a linked user backend does not exist or is invalid
     */
    public function setConfig(ConfigObject $config)
    {
        if ($config->backend === 'ldap') {
            $defaults = $this->getOpenLdapDefaults();
        } elseif ($config->backend === 'msldap') {
            $defaults = $this->getActiveDirectoryDefaults();
        } else {
            $defaults = new ConfigObject();
        }

        if ($config->user_backend && $config->user_backend !== 'none') {
            $userBackend = UserBackend::create($config->user_backend);
            if (! $userBackend instanceof LdapUserBackend) {
                throw new ConfigurationError('User backend "%s" is not of type LDAP', $config->user_backend);
            }

            if ($this->ds->getHostname() !== $userBackend->getDataSource()->getHostname()
                || $this->ds->getPort() !== $userBackend->getDataSource()->getPort()
            ) {
                // TODO(jom): Elaborate whether it makes sense to link directories on different hosts
                throw new ConfigurationError(
                    'It is required that a linked user backend refers to the '
                    . 'same directory as it\'s user group backend counterpart'
                );
            }

            $this->setUserBackend($userBackend);
            $defaults->merge(array(
                'user_base_dn'          => $userBackend->getBaseDn(),
                'user_class'            => $userBackend->getUserClass(),
                'user_name_attribute'   => $userBackend->getUserNameAttribute(),
                'user_filter'           => $userBackend->getFilter(),
                'domain'                => $userBackend->getDomain()
            ));
        }

        return $this
            ->setGroupBaseDn($config->base_dn)
            ->setUserBaseDn($config->get('user_base_dn', $defaults->get('user_base_dn', $this->getGroupBaseDn())))
            ->setGroupClass($config->get('group_class', $defaults->group_class))
            ->setUserClass($config->get('user_class', $defaults->user_class))
            ->setGroupNameAttribute($config->get('group_name_attribute', $defaults->group_name_attribute))
            ->setUserNameAttribute($config->get('user_name_attribute', $defaults->user_name_attribute))
            ->setGroupMemberAttribute($config->get('group_member_attribute', $defaults->group_member_attribute))
            ->setGroupFilter($config->group_filter)
            ->setUserFilter($config->user_filter)
            ->setNestedGroupSearch((bool) $config->get('nested_group_search', $defaults->nested_group_search))
            ->setDomain($defaults->get('domain', $config->domain));
    }

    /**
     * Return the configuration defaults for an OpenLDAP environment
     *
     * @return  ConfigObject
     */
    public function getOpenLdapDefaults()
    {
        return new ConfigObject(array(
            'group_class'               => 'group',
            'user_class'                => 'inetOrgPerson',
            'group_name_attribute'      => 'gid',
            'user_name_attribute'       => 'uid',
            'group_member_attribute'    => 'member',
            'nested_group_search'       => '0'
        ));
    }

    /**
     * Return the configuration defaults for an ActiveDirectory environment
     *
     * @return  ConfigObject
     */
    public function getActiveDirectoryDefaults()
    {
        return new ConfigObject(array(
            'group_class'               => 'group',
            'user_class'                => 'user',
            'group_name_attribute'      => 'sAMAccountName',
            'user_name_attribute'       => 'sAMAccountName',
            'group_member_attribute'    => 'member',
            'nested_group_search'       => '0'
        ));
    }

    /**
     * Inspect if this LDAP User Group Backend is working as expected by probing the backend
     *
     * Try to bind to the backend and fetch a single group to check if:
     * <ul>
     *  <li>Connection credentials are correct and the bind is possible</li>
     *  <li>At least one group exists</li>
     *  <li>The specified groupClass has the property specified by groupNameAttribute</li>
     * </ul>
     *
     * @return  Inspection  Inspection result
     */
    public function inspect()
    {
        $result = new Inspection('Ldap User Group Backend');

        // inspect the used connection to get more diagnostic info in case the connection is not working
        $result->write($this->ds->inspect());

        try {
            try {
                $groupQuery = $this->ds
                    ->select()
                    ->from($this->groupClass, array($this->groupNameAttribute))
                    ->setBase($this->groupBaseDn);

                if ($this->groupFilter) {
                    $groupQuery->setNativeFilter($this->groupFilter);
                }

                $res = $groupQuery->fetchRow();
            } catch (LdapException $e) {
                throw new AuthenticationException('Connection not possible', $e);
            }

            $result->write('Searching for: ' . sprintf(
                'objectClass "%s" in DN "%s" (Filter: %s)',
                $this->groupClass,
                $this->groupBaseDn ?: $this->ds->getDn(),
                $this->groupFilter ?: 'None'
            ));

            if ($res === false) {
                throw new AuthenticationException('Error, no groups found in backend');
            }

            $result->write(sprintf('%d groups found in backend', $groupQuery->count()));

            if (! isset($res->{$this->groupNameAttribute})) {
                throw new AuthenticationException(
                    'GroupNameAttribute "%s" not existing in objectClass "%s"',
                    $this->groupNameAttribute,
                    $this->groupClass
                );
            }
        } catch (AuthenticationException $e) {
            if (($previous = $e->getPrevious()) !== null) {
                $result->error($previous->getMessage());
            } else {
                $result->error($e->getMessage());
            }
        } catch (Exception $e) {
            $result->error(sprintf('Unable to validate backend: %s', $e->getMessage()));
        }

        return $result;
    }
}
