<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\Authentication\UserGroupBackend;
use Icinga\Data\Db\DbConnection;
use Icinga\User;

/**
 * Database user group backend
 */
class DbUserGroupBackend extends UserGroupBackend
{
    /**
     * Connection to the database
     *
     * @var DbConnection
     */
    private $conn;

    /**
     * Create a new database user group backend
     *
     * @param DbConnection $conn
     */
    public function __construct(DbConnection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * (non-PHPDoc)
     * @see UserGroupBackend::getMemberships() For the method documentation.
     */
    public function getMemberships(User $user)
    {
        $groups = array();
        $groupsStmt = $this->conn->getDbAdapter()
            ->select()
            ->from($this->conn->getTablePrefix() . 'group', array('name', 'parent'))
            ->query();
        foreach ($groupsStmt as $group) {
            $groups[$group->name] = $group->parent;
        }
        $memberships = array();
        $membershipsStmt = $this->conn->getDbAdapter()
            ->select()
            ->from($this->conn->getTablePrefix() . 'group_membership', array('group_name'))
            ->where('username = ?', $user->getUsername())
            ->query();
        foreach ($membershipsStmt as $membership) {
            $memberships[] = $membership->group_name;
            $parent = $groups[$membership->group_name];
            while (isset($parent)) {
                $memberships[] = $parent;
                $parent = $groups[$parent];
            }
        }
        return $memberships;
    }
}
