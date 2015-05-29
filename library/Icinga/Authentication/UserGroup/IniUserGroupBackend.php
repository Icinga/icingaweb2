<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Exception\StatementException;
use Icinga\Data\Filter\Filter;
use Icinga\Repository\IniRepository;
use Icinga\User;
use Icinga\Util\String;

class IniUserGroupBackend extends IniRepository implements UserGroupBackendInterface
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
    protected $filterColumns = array('group');

    /**
     * The value conversion rules to apply on a query or statement
     *
     * @var array
     */
    protected $conversionRules = array(
        'groups' => array(
            'created_at'    => 'date_time',
            'last_modified' => 'date_time',
            'users'         => 'comma_separated_string'
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
     * Add a new group to this backend
     *
     * @param   string  $target
     * @param   array   $data
     *
     * @throws  StatementException  In case the operation has failed
     */
    public function insert($target, array $data)
    {
        $data['created_at'] = time();
        parent::insert($target, $data);
    }

    /**
     * Update groups of this backend, optionally limited using a filter
     *
     * @param   string  $target
     * @param   array   $data
     * @param   Filter  $filter
     *
     * @throws  StatementException  In case the operation has failed
     */
    public function update($target, array $data, Filter $filter = null)
    {
        $data['last_modified'] = time();
        parent::update($target, $data, $filter);
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
            $groups[$group->group_name] = $group->parent;
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
