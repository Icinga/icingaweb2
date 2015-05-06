<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Repository\Repository;
use Icinga\User;
use Icinga\Util\String;

class IniUserGroupBackend extends Repository implements UserGroupBackendInterface
{
    /**
     * The query columns being provided
     *
     * @var array
     */
    protected $queryColumns = array(
        'groups' => array(
            'group'         => 'name',
            'group_name'    => 'name',
            'parent'        => 'parent',
            'parent_name'   => 'parent',
            'created_at'    => 'ctime',
            'last_modified' => 'mtime',
            'users'
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
     * Initialize this ini user group backend
     */
    protected function init()
    {
        $this->ds->getConfigObject()->setKeyColumn('name');
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
        $result = $this->select()->fetchAll();

        $groups = array();
        foreach ($result as $group) {
            $groups[$group->group_name] = $group->parent_name;
        }

        $username = strtolower($user->getUsername());
        $memberships = array();
        foreach ($result as $group) {
            if ($group->users && !in_array($group->group_name, $memberships)) {
                $users = array_map('strtolower', String::trimSplit($group->users));
                if (in_array($username, $users)) {
                    $memberships[] = $group->group_name;
                    $parent = $groups[$group->group_name];
                    while ($parent !== null) {
                        $memberships[] = $parent;
                        $parent = isset($groups[$parent]) ? $groups[$parent] : null;
                    }
                }
            }
        }

        return $memberships;
    }
}
