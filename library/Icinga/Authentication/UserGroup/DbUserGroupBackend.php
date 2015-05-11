<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Data\Filter\Filter;
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
            'group'         => 'name COLLATE utf8_general_ci',
            'group_name'    => 'name',
            'parent'        => 'parent COLLATE utf8_general_ci',
            'parent_name'   => 'parent',
            'created_at'    => 'UNIX_TIMESTAMP(ctime)',
            'last_modified' => 'UNIX_TIMESTAMP(mtime)'
        )
    );

    /**
     * The statement columns being provided
     *
     * @var array
     */
    protected $statementColumns = array(
        'group' => array(
            'created_at'    => 'ctime',
            'last_modified' => 'mtime'
        )
    );

    /**
     * The columns which are not permitted to be queried
     *
     * @var array
     */
    protected $filterColumns = array('group', 'parent');

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
