<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\Authentication\Auth;
use Icinga\DBUser;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Sql\Select;

trait DBUserManager
{
    public static $dashboardUsersTable = 'dashboard_user';

    public static $dashboardGroup = 'dashboard_group';

    public static $dashboardRole = 'dashboard_role';

    /**
     * A list of @see Role name some widgets are shared with
     *
     * @var string[]
     */
    private $roles = [];

    /**
     * A list of user group names which the dashboard is shared with
     *
     * @var string[]
     */
    private $groups = [];

    /**
     * A list of @see DBUser which references in some way with the dashboards
     *
     * @var DBUser[]
     */
    private $users = [];

    /**
     * This dashboard's user being currently logged in
     *
     * @var DBUser
     */
    private $authUser;

    /**
     * Load all dashboard users from DB and cache them for later use
     *
     * @return void
     */
    protected function initDashboardUsers()
    {
        $conn = DashboardHome::getConn();
        $dbUsers = $conn->select((new Select())
            ->columns('*')
            ->from(self::$dashboardUsersTable));

        $users = [];
        foreach ($dbUsers as $user) {
            $users[$user->name] = (new DBUser($user->name))->setIdentifier($user->id);
        }

        $this->setUsers($users);
    }

    /**
     * Load all dashboard groups from DB and cache them for later use
     *
     * @return void
     */
    protected function initDashboardGroups()
    {
        $dbGroups = DashboardHome::getConn()->select((new Select())
            ->columns('*')
            ->from(self::$dashboardGroup));

        foreach ($dbGroups as $group) {
            $this->groups[$group->name] = $group->id;
        }
    }

    /**
     * Load all dashboard roles from DB and cache them for later use
     *
     * @return void
     */
    protected function initDashboardRoles()
    {
        $dbRoles = DashboardHome::getConn()->select((new Select())
            ->columns('*')
            ->from(self::$dashboardRole));

        foreach ($dbRoles as $role) {
            $this->roles[$role->role] = $role->id;
        }
    }

    /**
     * Get the authenticated user currently being logged in
     *
     * @return DBUser
     */
    public function getAuthUser()
    {
        if (! $this->authUser) {
            $user = Auth::getInstance()->getUser();
            $this->authUser = (new DBUser($user->getUsername()))->extractFrom($user);

            $this->manage($this->authUser);
        }

        return $this->authUser;
    }

    /**
     * Set the authenticated user currently being logged in
     *
     * @param DBUser $user
     *
     * @return $this
     */
    public function setAuthUser(DBUser $user)
    {
        $this->manage($user);
        $this->authUser = $user;

        return $this;
    }

    /**
     * Get a normal not authenticated user by username
     *
     * @param string $name
     *
     * @return DBUser
     */
    public function getUser($name)
    {
        if (! $this->userExists($name)) {
            throw new ProgrammingError('Trying to retrieve an invalid dashboard user "%s"', $name);
        }

        return $this->users[$name];
    }

    /**
     * Get a list of dashboard users loaded from DB
     *
     * @return array
     */
    public function getUsers()
    {
        if (! $this->authUser) {
            return $this->users;
        }

        return array_diff_key($this->users, [$this->getAuthUser()->getUsername() => 0]);
    }

    /**
     * Set this dashboard's users
     *
     * @param DBUser|DBUser[] $users
     */
    public function setUsers($users)
    {
        if ($users instanceof DBUser) {
            $users = [$users->getUsername() => $users];
        }

        $this->users = $users;
    }

    /**
     * Get all the dashboard group names
     *
     * @return string[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get all the dashboard user role names
     *
     * @return string[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get whether a normal not authenticated user exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function userExists($name)
    {
        return array_key_exists($name, $this->users);
    }

    /**
     * Manage the given db user
     *
     * @param DBUser $user
     * @param string $action
     *
     * @return $this
     */
    public function manage(DBUser $user, $action = 'UPDATE')
    {
        $conn = DashboardHome::getConn();
        $this->initDashboardUsers();

        if (! $this->userExists($user->getUsername())) {
            $conn->insert(self::$dashboardUsersTable, ['name' => $user->getUsername()]);
            $user->setIdentifier($conn->lastInsertId());

            $this->users[$user->getUsername()] = $user;
        } elseif ($action === 'UPDATE') {
            $user->setIdentifier($this->getUser($user->getUsername())->getIdentifier());

            $conn->update(self::$dashboardUsersTable, ['name' => $user->getUsername()], [
                'id = ?' => $user->getIdentifier()
            ]);
        } elseif ($action === 'DELETE') {
            $conn->delete(self::$dashboardUsersTable, ['id = ?' => $user->getIdentifier()]);
            unset($this->users[$user->getUsername()]);
        }

        return $this;
    }
}
