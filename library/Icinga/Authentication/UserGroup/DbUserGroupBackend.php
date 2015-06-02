<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Data\Filter\Filter;
use Icinga\Exception\NotFoundError;
use Icinga\Repository\DbRepository;
use Icinga\Repository\RepositoryQuery;
use Icinga\User;

class DbUserGroupBackend extends DbRepository implements UserGroupBackendInterface
{
    /**
     * The query columns being provided
     *
     * @var array
     */
    protected $queryColumns = array(
        'group' => array(
            'group_id'      => 'g.id',
            'group'         => 'g.name COLLATE utf8_general_ci',
            'group_name'    => 'g.name',
            'parent'        => 'g.parent',
            'created_at'    => 'UNIX_TIMESTAMP(g.ctime)',
            'last_modified' => 'UNIX_TIMESTAMP(g.mtime)'
        ),
        'group_membership' => array(
            'group_id'      => 'gm.group_id',
            'user'          => 'gm.username COLLATE utf8_general_ci',
            'user_name'     => 'gm.username',
            'created_at'    => 'UNIX_TIMESTAMP(gm.ctime)',
            'last_modified' => 'UNIX_TIMESTAMP(gm.mtime)'
        )
    );

    /**
     * The table aliases being applied
     *
     * @var array
     */
    protected $tableAliases = array(
        'group'             => 'g',
        'group_membership'  => 'gm'
    );

    /**
     * The statement columns being provided
     *
     * @var array
     */
    protected $statementColumns = array(
        'group' => array(
            'group_id'      => 'id',
            'group_name'    => 'name',
            'parent'        => 'parent',
            'created_at'    => 'ctime',
            'last_modified' => 'mtime'
        ),
        'group_membership' => array(
            'group_id'      => 'group_id',
            'group_name'    => 'group_id',
            'user_name'     => 'username',
            'created_at'    => 'ctime',
            'last_modified' => 'mtime'
        )
    );

    /**
     * The columns which are not permitted to be queried
     *
     * @var array
     */
    protected $filterColumns = array('group', 'user');

    /**
     * The value conversion rules to apply on a query or statement
     *
     * @var array
     */
    protected $conversionRules = array(
        'group'             => array(
            'parent'        => 'group_id'
        ),
        'group_membership'  => array(
            'group_name'    => 'group_id'
        )
    );

    /**
     * Initialize this database user group backend
     */
    protected function init()
    {
        if (! $this->ds->getTablePrefix()) {
            $this->ds->setTablePrefix('icingaweb_');
        }
    }

    /**
     * Insert a table row with the given data
     *
     * @param   string  $table
     * @param   array   $bind
     */
    public function insert($table, array $bind)
    {
        $bind['created_at'] = date('Y-m-d H:i:s');
        parent::insert($table, $bind);
    }

    /**
     * Update table rows with the given data, optionally limited by using a filter
     *
     * @param   string  $table
     * @param   array   $bind
     * @param   Filter  $filter
     */
    public function update($table, array $bind, Filter $filter = null)
    {
        $bind['last_modified'] = date('Y-m-d H:i:s');
        parent::update($table, $bind, $filter);
    }

    /**
     * Delete table rows, optionally limited by using a filter
     *
     * @param   string  $table
     * @param   Filter  $filter
     */
    public function delete($table, Filter $filter = null)
    {
        if ($table === 'group') {
            parent::delete('group_membership', $filter);
            $idQuery = $this->select(array('group_id'));
            if ($filter !== null) {
                $idQuery->applyFilter($filter);
            }

            $this->update('group', array('parent' => null), Filter::where('parent', $idQuery->fetchColumn()));
        }

        parent::delete($table, $filter);
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
        $groupQuery = $this->ds
            ->select()
            ->from(
                array('g' => $this->prependTablePrefix('group')),
                array(
                    'group_name'    => 'g.name',
                    'parent_name'   => 'gg.name'
                )
            )->joinLeft(
                array('gg' => $this->prependTablePrefix('group')),
                'g.parent = gg.id',
                array()
            );

        $groups = array();
        foreach ($groupQuery as $group) {
            $groups[$group->group_name] = $group->parent_name;
        }

        $membershipQuery = $this
            ->select()
            ->from('group_membership', array('group_name'))
            ->where('user_name', $user->getUsername());

        $memberships = array();
        foreach ($membershipQuery as $membership) {
            $memberships[] = $membership->group_name;
            $parent = $groups[$membership->group_name];
            while ($parent !== null) {
                $memberships[] = $parent;
                // Usually a parent is an existing group, but since we do not have a constraint on our table..
                $parent = isset($groups[$parent]) ? $groups[$parent] : null;
            }
        }

        return $memberships;
    }

    /**
     * Join group into group_membership
     *
     * @param   RepositoryQuery     $query
     */
    protected function joinGroup(RepositoryQuery $query)
    {
        $query->getQuery()->join(
            $this->requireTable('group'),
            'gm.group_id = g.id',
            array()
        );
    }

    /**
     * Join group_membership into group
     *
     * @param   RepositoryQuery     $query
     */
    protected function joinGroupMembership(RepositoryQuery $query)
    {
        $query->getQuery()->join(
            $this->requireTable('group_membership'),
            'g.id = gm.group_id',
            array()
        );
    }

    /**
     * Fetch and return the corresponding id for the given group's name
     *
     * @param   string|array    $groupName
     *
     * @return  int
     *
     * @throws  NotFoundError
     */
    protected function persistGroupId($groupName)
    {
        if (! $groupName || empty($groupName) || is_int($groupName)) {
            return $groupName;
        }

        if (is_array($groupName)) {
            if (is_int($groupName[0])) {
                return $groupName; // In case the array contains mixed types...
            }

            $groupIds = $this->ds
                ->select()
                ->from($this->prependTablePrefix('group'), array('id'))
                ->where('name', $groupName)
                ->fetchColumn();
            if (empty($groupIds)) {
                throw new NotFoundError('No groups found matching one of: %s', implode(', ', $groupName));
            }

            return $groupIds;
        }

        $groupId = $this->ds
            ->select()
            ->from($this->prependTablePrefix('group'), array('id'))
            ->where('name', $groupName)
            ->fetchOne();
        if ($groupId === false) {
            throw new NotFoundError('Group "%s" does not exist', $groupName);
        }

        return $groupId;
    }
}
