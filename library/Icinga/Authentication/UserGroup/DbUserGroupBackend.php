<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Repository\DbRepository;
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
            'group_name'    => 'name',
            'parent_name'   => 'parent',
            'created_at'    => 'UNIX_TIMESTAMP(ctime)',
            'last_modified' => 'UNIX_TIMESTAMP(mtime)'
        )
    );

    /**
     * The default sort rules to be applied on a query
     *
     * @var array
     */
    protected $sortRules = array(
        'group_name' => array(
            'columns'   => array(
                'group_name',
                'parent_name'
            )
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
     * Return the groups the given user is a member of
     *
     * @param   User    $user
     *
     * @return  array
     */
    public function getMemberships(User $user)
    {
        $groups = array();
        $groupsStmt = $this->select(array('group_name', 'parent_name'))->getQuery()->getSelectQuery()->query();
        foreach ($groupsStmt as $group) {
            $groups[$group->group_name] = $group->parent_name;
        }

        $memberships = array();
        $membershipsStmt = $this->ds->getDbAdapter() // TODO: Use the join feature, once available
            ->select()
            ->from($this->ds->getTablePrefix() . 'group_membership', array('group_name'))
            ->where('username = ?', $user->getUsername())
            ->query();
        foreach ($membershipsStmt as $membership) {
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
}
