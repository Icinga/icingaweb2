<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\DBUser;
use Icinga\Exception\NotFoundError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\HomeMenu;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Widget\Dashboard;
use ipl\Sql\Select;
use ipl\Web\Url;

/**
 * Allows to easily manage dashboard homes
 */
trait DashboardManager
{
    use DBUserManager;

    /**
     * A list of @see DashboardHome
     *
     * @var DashboardHome[]
     */
    private $homes = [];

    /**
     * An action which determines which action is to be taken for the dashboards
     *
     * @var string
     */
    private $action;

    /**
     * Load dashboard homes with all their panes and dashlets
     */
    public function load()
    {
        $this->loadHomesFromMenu();
        $this->loadDashboards();

        foreach ([DashboardHome::AVAILABLE_DASHLETS, DashboardHome::SHARED_DASHBOARDS] as $name) {
            if (! $this->hasHome($name)) {
                $home = new DashboardHome($name);
                $this->manageHome($home);
            }
        }
    }

    /**
     * Load homes from the navigation menu
     *
     * @return $this
     */
    public function loadHomesFromMenu()
    {
        $menu = new HomeMenu();
        /** @var DashboardHome $home */
        foreach ($menu->getItem('dashboard')->getChildren() as $home) {
            if (! $home instanceof DashboardHome) {
                continue;
            }

            $this->homes[$home->getName()] = $home;
        }

        $this->initAndGetDefaultHome();

        return $this;
    }

    /**
     * Load dashboard panes belonging to the given or active home being loaded
     *
     * @param ?string $homeName
     */
    public function loadDashboards($homeName = null)
    {
        if ($homeName && $this->hasHome($homeName)) {
            $home = $this->getHome($homeName);
            $home->setAuthUser($this->getAuthUser());
            $this->activateHome($home);

            $home // Loading priority order is essential
                ->loadSystemDashboards()
                ->loadOverridingDashboards()
                ->loadUserDashboards();

            return;
        }

        $requestUrl = Url::fromRequest();
        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE) {
            $home = $this->initAndGetDefaultHome();
        } else {
            $homeParam = $requestUrl->getParam('home');
            if (empty($homeParam) || ! $this->hasHome($homeParam)) {
                $home = $this->rewindHomes();
                if (! $home) {
                    return;
                }
            } else {
                $home = $this->getHome($homeParam);
            }
        }

        $this->activateHome($home);
        $home->setAuthUser($this->getAuthUser());
        $home // Loading priority order is essential
            ->loadSystemDashboards()
            ->loadOverridingDashboards()
            ->loadUserDashboards();
    }

    /**
     * Get an action which determines which action is to be taken for the dashboards
     *
     * This action can one of the following [INSERT, UPDATE, DELETE]
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set an action which determines which action is to be taken for the dashboards
     *
     * This action can one of the following [INSERT, UPDATE, DELETE]
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Check whether the given home exists in the DB, i.e whether it's not loaded yet
     *
     * @param DashboardHome $home
     *
     * @return bool
     */
    public static function homePersists(DashboardHome $home)
    {
        $conn = DashboardHome::getConn();
        $result = $conn->select((new Select())
            ->columns('id')
            ->from(DashboardHome::TABLE)
            ->where(['name = ?' => $home->getName()]))->fetch();

        if ($result) {
            $home->setIdentifier($result->id);

            return true;
        }

        return false;
    }

    /**
     * Manage the given dashboard home and its references, the actions are INSERT, UPDATE and eventually DELETE
     *
     * In case of duplicate errors or similar, no exception is raised, only being logged,
     * hence the callers have to take care of error handling and follow-up by themselves, either
     * beforehand or after calling this method and any changes already applied are rolled back
     *
     * @param DashboardHome  $home   The actual home to be managed
     * @param ?DashboardHome $origin The original home with the original pane(s) & dashlet(s) to be updated
     *
     * @return void
     */
    public function manageHome(DashboardHome $home, DashboardHome $origin = null)
    {
        if ($this->getAction() === 'DELETE') {
            $this->removeHome($home->getName());

            return;
        }

        $conn = $home::getConn();
        $conn->beginTransaction();

        try {
            if (! self::homePersists($home)) {
                $conn->insert(DashboardHome::TABLE, [
                    'name'  => $home->getName(),
                    'label' => $home->getLabel()
                ]);

                $home->setIdentifier($conn->lastInsertId());
            }

            // Prevent from being updated the non-editable homes
            if (! in_array($home->getName(), DashboardHome::DEFAULT_HOME_ENUMS, true)) {
                $conn->update(DashboardHome::TABLE, ['label' => $home->getLabel()], [
                    'id = ?' => $home->getIdentifier()
                ]);
            }

            $panes = $home->getPanes();
            $home->setPanes([]);
            if ($this->hasHome($home->getName())) {
                $home->setActive();
                $home->loadUserDashboards();
            }

            $user = $this->getAuthUser();
            foreach ($panes as $pane) {
                $paneId = $home::getSHA1($user->getUsername() . $home->getName() . $pane->getName());
                $pane->setOwner($user->getUsername());

                // Due to public & shared dashboards are being created by different users,
                // we have to use the original ID to prevent dashboard_id constraint from failing
                if (
                    $pane->getPaneId()
                    && (
                        $pane->getType() === Dashboard::PUBLIC_DS
                        || $pane->getType() === Dashboard::SHARED
                    )
                ) {
                    $paneId = $pane->getPaneId();
                }

                if ($pane->isUserWidget() && ! $pane->isOverridingWidget()) {
                    $createNewPane = false;
                    // This code block is only used to simplify the process of determining
                    // whether the given pane needs to be inserted into the database
                    if ($pane->getType() !== Dashboard::SHARED && $pane->getType() !== Dashboard::PUBLIC_DS) {
                        if ($origin && $origin->getName() === $home->getName() && ! $home->hasPane($pane->getName())) {
                            $createNewPane = true;
                        } elseif (
                            ! $home->hasPane($pane->getName())
                            && (
                                ! $origin
                                || ! $origin->hasPane($pane->getName())
                                || ! $pane->getPaneId()
                            )
                        ) {
                            $createNewPane = true;
                        }
                    } elseif (! $home->panePersists($pane)) {
                        // The given pane is either a shared or public one and doesn't exist in the DB yet
                        $createNewPane = true;
                    }

                    if ($createNewPane) {
                        $conn->insert(Pane::TABLE, [
                            'id'        => $paneId,
                            'home_id'   => $home->getIdentifier(),
                            'name'      => $pane->getName(),
                            'label'     => $pane->getTitle(),
                        ]);
                    } elseif (
                        ! $origin
                        || $origin->getName() === $home->getName()
                        || ! $home->hasPane($pane->getName())
                    ) {
                        $data = [
                            'id'        => $paneId,
                            'home_id'   => $home->getIdentifier(),
                            'name'      => $pane->getName(),
                            'label'     => $pane->getTitle(),
                        ];

                        if ($home->hasPane($pane->getName())) {
                            $orgPane = $home->getPane($pane->getName());
                            // It might be the case that we only want to update dashlets
                            // so this will prevent the panes from being updated as well
                            $data = array_filter(array_diff_assoc($pane->toArray(), $orgPane->toArray()));
                        }

                        if (! empty($data)) {
                            $conn->update(Pane::TABLE, $data, ['id = ?' => $pane->getPaneId()]);
                        }
                    } else {
                        // It might be the case that the user moves a dashlet from one to another
                        // existing home and pane, even if this isn't a real error we will still
                        // end up here, so just log it and that's it
                        Logger::error('There is already a pane "%s" within this home', $pane->getTitle());

                        if (! $pane->hasDashlets()) {
                            // We can simply abort the execution here, any applied changes
                            // will be rolled back automatically by PDO
                            return;
                        }
                    }
                } elseif (! $pane->isUserWidget()) {
                    $conn->insert(Pane::DASHBOARD_OVERRIDE, [
                        'dashboard_id'  => $paneId,
                        'user_id'       => $user->getIdentifier(),
                        'label'         => $pane->getTitle(),
                        'disabled'      => (int) $pane->isDisabled()
                    ]);
                } elseif ($pane->isOverridingWidget()) {
                    $conn->update(Pane::DASHBOARD_OVERRIDE, [
                        'label'     => $pane->getTitle(),
                        'disabled'  => (int) $pane->isDisabled()
                    ], [
                        'user_id = ?'       => $user->getIdentifier(),
                        'dashboard_id = ?'  => $pane->getPaneId()
                    ]);
                }

                foreach ($pane->getDashlets() as $dashlet) {
                    $dashletId = $home::getSHA1(
                        $user->getUsername() . $home->getName() . $pane->getName() . $dashlet->getName()
                    );
                    if ($dashlet->isUserWidget() && ! $dashlet->isOverridingWidget()) {
                        if ($dashlet->getDashletId() && $dashlet->getType() === Dashboard::SHARED) {
                            $dashletId = $dashlet->getDashletId();
                        }

                        $createNewDashlet = false;
                        // As the process of determining whether the given dashlet needs to be inserted
                        // into the database is obviously 🙄 quite long and tricky, the following
                        // code block is intended for simplifying the if statement
                        if ($dashlet->getType() !== Dashboard::SHARED && $dashlet->getType() !== Dashboard::PUBLIC_DS) {
                            if (
                                ! $origin
                                || ! $home->hasPane($pane->getName())
                                && (
                                    $origin->getName() === $home->getName()
                                    && (
                                        ! $origin->hasPane($pane->getName())
                                        || ! $origin->getPane($pane->getName())->hasDashlet($dashlet->getName())
                                    )
                                )
                            ) {
                                $createNewDashlet = true;
                            } elseif (
                                $home->hasPane($pane->getName())
                                && ! $home->getPane($pane->getName())->hasDashlet($dashlet->getName())
                                && (
                                    ! $origin->hasPane($pane->getName())
                                    || ! $origin->getPane($pane->getName())->hasDashlet($dashlet->getName())
                                )
                            ) {
                                $createNewDashlet = true;
                            }
                        } elseif (! $pane::dashletPersists($dashlet)) {
                            $createNewDashlet = true;
                        }

                        if ($createNewDashlet) {
                            $conn->insert(Dashlet::TABLE, [
                                'id'            => $dashletId,
                                'dashboard_id'  => $paneId,
                                'name'          => $dashlet->getName(),
                                'label'         => $dashlet->getTitle(),
                                'url'           => $dashlet->getUrl()->getRelativeUrl()
                            ]);
                        } elseif (
                            ! $origin
                            || $origin->getName() === $home->getName()
                            || ! $home->hasPane($pane->getName())
                            || ! $home->getPane($pane->getName())->hasDashlet($dashlet->getName())
                        ) {
                            $conn->update(Dashlet::TABLE, [
                                'id'            => $dashletId,
                                'dashboard_id'  => $paneId,
                                'name'          => $dashlet->getName(),
                                'label'         => $dashlet->getTitle(),
                                'url'           => $dashlet->getUrl()->getRelativeUrl()
                            ], ['id = ?' => $dashlet->getDashletId()]);
                        } else {
                            // We only have to log it, the caller should care about the error handling
                            Logger::error('There is already a dashlet "%s" within this pane', $dashlet->getTitle());

                            // We can safely abort the execution here, any applied changes
                            // will be rolled back automatically by PDO
                            return;
                        }
                    } elseif (! $dashlet->isUserWidget()) {
                        $conn->insert(Dashlet::OVERRIDING_TABLE, [
                            'dashlet_id'    => $dashletId,
                            'user_id'       => $user->getIdentifier(),
                            'label'         => $dashlet->getTitle(),
                            'url'           => $dashlet->getUrl()->getRelativeUrl(),
                            'disabled'      => (int) $dashlet->isDisabled()
                        ]);
                    } elseif ($dashlet->isOverridingWidget()) {
                        $conn->update(Dashlet::OVERRIDING_TABLE, [
                            'label'     => $dashlet->getTitle(),
                            'url'       => $dashlet->getUrl()->getRelativeUrl(),
                            'disabled'  => (int) $dashlet->isDisabled()
                        ], [
                            'user_id = ?'       => $user->getIdentifier(),
                            'dashlet_id = ?'    => $dashlet->getDashletId()
                        ]);
                    }

                    if (! $dashlet->getDashletId()) {
                        $dashlet->setDashletId($dashletId);
                    }

                    $dashlet
                        ->setAdditional('with_groups', $pane->getAdditional('with_groups'))
                        ->setAdditional('with_roles', $pane->getAdditional('with_roles'));
                }

                if (! $pane->getPaneId()) {
                    $pane->setPaneId($paneId);
                }
            }

            $home->setPanes($panes);
            $this->saveHomeMembership($home);

            $this->reorderHome($home);

            $conn->commitTransaction();
        } catch (\Exception $err) {
            Logger::error($err);
            $conn->rollBackTransaction();
        }
    }

    /**
     * @param DashboardHome $home
     *
     * @return $this
     */
    public function addHome(DashboardHome $home)
    {
        $this->homes[$home->getName()] = $home;

        return $this;
    }

    /**
     * Check whether the given home exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHome($name)
    {
        return array_key_exists($name, $this->getHomes());
    }

    /**
     * Activate the given home and set all the others to inactive
     *
     * @param DashboardHome $home
     *
     * @return $this
     */
    public function activateHome(DashboardHome $home)
    {
        $activeHome = $this->getActiveHome();
        if ($activeHome && $activeHome->getName() !== $home->getName()) {
            $activeHome->setActive(false);
        }

        $home->setActive();

        return $this;
    }

    /**
     * Get the active home that is being loaded
     *
     * @return ?DashboardHome
     */
    public function getActiveHome()
    {
        $active = null;
        foreach ($this->getHomes() as $home) {
            if ($home->getActive()) {
                $active = $home;

                break;
            }
        }

        return $active;
    }

    /**
     * Get Icinga Web 2's default dashboard home
     *
     * @return DashboardHome
     */
    public function initAndGetDefaultHome()
    {
        $defaultHome = null;
        if ($this->hasHome(DashboardHome::DEFAULT_HOME)) {
            $defaultHome = $this->getHome(DashboardHome::DEFAULT_HOME);
            if ($defaultHome->getAuthUser()->getUsername() === $this->getAuthUser()->getUsername()) {
                return $defaultHome;
            }
        }

        $conn = DashboardHome::getConn();
        $membership = null;
        if (! $defaultHome) {
            $defaultHome = new DashboardHome(DashboardHome::DEFAULT_HOME);
            $this->manageHome($defaultHome);
        } else {
            $membership = $conn->select((new Select())
                ->columns('disabled')
                ->from($defaultHome->getTableMembership())
                ->where(['home_id = ?' => $defaultHome->getIdentifier()]))->fetch();
        }

        if ($membership) {
            $defaultHome->setDisabled($membership->disabled);
        }

        $defaultHome->setAuthUser($this->getAuthUser());
        $this->homes[$defaultHome->getName()] = $defaultHome;

        return $defaultHome;
    }

    /**
     * Update or Insert the given home order priority
     *
     * @param DashboardHome $home
     * @param ?int          $position
     *
     * @return $this
     */
    public function reorderHome(DashboardHome $home, int $position = null)
    {
        $conn = DashboardHome::getConn();
        if ($home->getType() === Dashboard::SYSTEM || $home->getPriority() < 1) {
            $conn->insert('dashboard_home_order', [
                'priority' => 0,
                'user_id'  => $this->getAuthUser()->getIdentifier(),
                'home_id'  => $home->getIdentifier()
            ]);
        } else {
            // TODO(yh): Find a way to reorder dashboard homes in the ui
        }

        return $this;
    }

    /**
     * Get a dashboard home by the given name or id
     *
     * @param string|int $nameOrId
     *
     * @return DashboardHome
     */
    public function getHome($nameOrId)
    {
        if (is_int($nameOrId)) {
            foreach ($this->homes as $home) {
                if ($home->getIdentifier() === (int) $nameOrId) {
                    return $home;
                }
            }
        }

        if ($this->hasHome($nameOrId)) {
            return $this->homes[$nameOrId];
        }

        throw new ProgrammingError('Trying to retrieve invalid dashboard home "%s"', $nameOrId);
    }

    /**
     * Get this user's all home navigation items
     *
     * @return DashboardHome[]
     */
    public function getHomes()
    {
        return $this->homes;
    }

    /**
     * Reset the current position of the internal homes
     *
     * @return false|DashboardHome
     */
    public function rewindHomes()
    {
        $homes = array_filter($this->getHomes(), function ($home) {
            return $home->getName() !== DashboardHome::AVAILABLE_DASHLETS
                && $home->getName() !== DashboardHome::SHARED_DASHBOARDS;
        });

        return reset($homes);
    }

    /**
     * Unset the given home if exists from the list
     *
     * @param  string $name
     *
     * @return $this
     */
    public function unsetHome($name)
    {
        if ($this->hasHome($name)) {
            unset($this->homes[$name]);
        }

        return $this;
    }

    /**
     * Remove the given home from the database
     *
     * @param  string $name
     *
     * @return $this
     */
    public function removeHome($name)
    {
        if (! $this->hasHome($name)) {
            throw new ProgrammingError('Trying to remove invalid dashboard home "%s"', $name);
        }

        $conn = DashboardHome::getConn();
        $home = $this->getHome($name);
        if (! $home->isDisabled()) {
            if (
                $home->getType() === Dashboard::SYSTEM
                || in_array($home->getName(), DashboardHome::DEFAULT_HOME_ENUMS, true)
            ) {
                $home
                    // Prevents any updates on the panes, as we only want to disable the home
                    ->setPanes([])
                    ->setDisabled(true);
                $this->manageHome($home);
            } else {
                $purgeFromDB = false;
                if ($home->getType() === Dashboard::PUBLIC_DS || $home->getType() === Dashboard::SHARED) {
                    $homeUsers = $home->getAdditional('unshared_users');
                    if ($homeUsers instanceof DBUser) {
                        $purgeFromDB = $homeUsers->getUsername() === '*';
                    } else {
                        $purgeFromDB = (bool) array_filter($homeUsers, function ($DBUser) {
                            return $DBUser->getUsername() === '*';
                        });
                    }
                }

                $home->removePanes();
                if ($purgeFromDB || $home->getType() === Dashboard::PRIVATE_DS) {
                    $conn->delete(DashboardHome::TABLE, ['id = ?' => $home->getIdentifier()]);
                } else {
                    $conn->delete($home->getTableMembership(), [
                        'home_id = ?'   => $home->getIdentifier(),
                        'user_id = ?'   => $this->getAuthUser()->getIdentifier()
                    ]);
                }
            }
        }

        // Since the navigation menu is not loaded that fast, we need to unset
        // the just deleted home from our list as well
        $this->unsetHome($home->getName());

        return $this;
    }

    /**
     * Return an array with home name=>label format used for comboboxes
     *
     * @param bool $skipDisabled Whether to skip disabled homes
     * @param bool $onlyShared   Whether to only fetch shared dashboard names
     *
     * @return array
     */
    public function getHomeKeyNameArray(bool $skipDisabled = true, bool $onlyShared = false)
    {
        $list = [];
        foreach ($this->getHomes() as $name => $home) {
            if (
                $onlyShared
                && $home->getType() !== Dashboard::SHARED
                || (
                    $home->isDisabled()
                    && $skipDisabled
                )
                // User is not allowed to add new content directly to this dashboard homes
                || $home->getName() === DashboardHome::AVAILABLE_DASHLETS
                || $home->getName() === DashboardHome::SHARED_DASHBOARDS
            ) {
                continue;
            }

            $list[$name] = $home->getLabel();
        }

        return $list;
    }

    /**
     * Insert, modify or delete a home => user membership
     *
     * @param DashboardHome $home
     *
     * @return $this
     */
    public function saveHomeMembership(DashboardHome $home)
    {
        $home->loadMembers();
        $conn = $home::getConn();
        foreach (array_unique(array_merge($home->getAdditional('with_users'), [$this->getAuthUser()])) as $user) {
            if (! $home->hasMember($user->getUsername())) {
                $data = [
                    'home_id'   => $home->getIdentifier(),
                    'disabled'  => (int) $home->isDisabled(),
                    'type'      => $home->getType() !== Dashboard::SYSTEM ? $home->getType() : Dashboard::PRIVATE_DS
                ];
                if ($this->userExists($user->getUsername())) {
                    $data['user_id'] = $this->getUser($user->getUsername())->getIdentifier();
                } else {
                    $conn->insert(DBUserManager::$dashboardUsersTable, ['name' => $user->getUsername()]);
                    $data['user_id'] = $conn->lastInsertId();
                }

                if ($user->getUsername() === $this->getAuthUser()->getUsername()) {
                    $data['owner'] = 'y';
                }

                $conn->insert($home->getTableMembership(), $data);
            } else {
                $conn->update($home->getTableMembership(), ['disabled' => (int) $home->isDisabled()], [
                    'user_id = ?' => $user->getIdentifier(),
                    'home_id = ?' => $home->getIdentifier()
                ]);
            }
        }

        $this->manageRolesAndGroupMembers($home);
        $this->initDashboardUsers();

        // Also update pane & dashlet memberships
        foreach ($home->getPanes() as $pane) {
            $pane->setHome($home);
            $this->savePaneDashletMembership($pane);
        }

        return $this;
    }

    /**
     * Manage the given widget's relationships with dashboard groups and roles
     *
     * @param DashboardHome|Dashlet|Pane $widget
     *
     * @return void
     */
    public function manageRolesAndGroupMembers($widget)
    {
        $widget->loadMembers();
        $this->initDashboardRoles();
        $this->initDashboardGroups();

        $conn = DashboardHome::getConn();
        $identityType = 'GROUP';
        $entries = $widget->getAdditional('with_groups');
        if (! empty($entries)) {
            $widget->setAdditional('with_groups', []);
        }

        if (empty($entries)) {
            $identityType = 'ROLE';
            $entries = $widget->getAdditional('with_roles');
            $widget->setAdditional('with_roles', []);
        }

        if (empty($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            $condition = [];
            switch ($widget) {
                case $widget instanceof DashboardHome:
                    $condition = ['home_id = ?' => $widget->getIdentifier()];
                    $data = ['home_id' => $widget->getIdentifier()];

                    break;
                case $widget instanceof Pane:
                    $condition = ['dashboard_id = ?' => $widget->getPaneId()];
                    $data['dashboard_id'] = $widget->getPaneId();

                    break;
                case $widget instanceof Dashlet:
                    $condition = ['dashlet_id = ?' => $widget->getDashletId()];
                    $data['dashlet_id'] = $widget->getDashletId();
            }

            if ($identityType === 'GROUP') {
                if (array_key_exists($entry, $this->getGroups())) {
                    $data['group_id'] = $this->getGroups()[$entry];
                } else {
                    $conn->insert(DBUserManager::$dashboardGroup, ['name' => $entry]);
                    $data['group_id'] = $conn->lastInsertId();
                }

                $condition['group_id = ?'] = $data['group_id'];
            } else {
                if (array_key_exists($entry, $this->getRoles())) {
                    $data['role_id'] = $this->getRoles()[$entry];
                } else {
                    $conn->insert(DBUserManager::$dashboardRole, ['role' => $entry]);
                    $data['role_id'] = $conn->lastInsertId();
                }

                $condition['role_id = ?'] = $data['role_id'];
            }

            $newMember = false;
            if ($identityType !== 'GROUP' && ! $widget->hasMemberRole($entry)) {
                $newMember = true;
            } elseif ($identityType === 'GROUP' && ! $widget->hasMemberGroup($entry)) {
                $newMember = true;
            }

            if ($newMember) {
                $conn->insert(Dashboard::GROUP_ROLE_TABLE, $data);
            } else {
                $conn->update(Dashboard::GROUP_ROLE_TABLE, $data, $condition);
            }
        }

        $this->manageRolesAndGroupMembers($widget);
    }

    /**
     * Update or insert pane & dashlet memberships in a recursive chain
     *
     * @param Pane|Dashlet $paneOrDashlet
     *
     * @return $this
     */
    public function savePaneDashletMembership($paneOrDashlet)
    {
        $conn = DashboardHome::getConn();
        $authUser = $this->getAuthUser();

        $newIdentifier = null;
        $generateId = $paneOrDashlet->getType() !== Dashboard::SHARED
            && $paneOrDashlet->getType() !== Dashboard::PUBLIC_DS;

        $username = $authUser->getUsername();
        // Even when the changes aren't committed yet, it might be
        // possible that the passed widget has moved somewhere else,
        // so we have to make sure to update only the desired widget membership
        if ($paneOrDashlet instanceof Pane) {
            $previousId = $paneOrDashlet->getPaneId();
            if ($generateId) {
                $newIdentifier = DashboardHome::getSHA1(
                    $username . $paneOrDashlet->getHome()->getName() . $paneOrDashlet->getName()
                );

                $paneOrDashlet->setPaneId($newIdentifier);
            }
        } else {
            $previousId = $paneOrDashlet->getDashletId();
            if ($generateId) {
                $pane = $paneOrDashlet->getPane();
                $newIdentifier = DashboardHome::getSHA1(
                    $username . $pane->getHome()->getName() . $pane->getName() . $paneOrDashlet->getName()
                );

                $paneOrDashlet->setDashletId($newIdentifier);
            }
        }

        $paneOrDashlet->loadMembers();
        foreach (array_unique(array_merge($paneOrDashlet->getAdditional('with_users'), [$authUser])) as $user) {
            if ($paneOrDashlet->getType() === Dashboard::SYSTEM) {
                continue;
            }

            $data = ['type' => $paneOrDashlet->getType() ?: Dashboard::PRIVATE_DS];
            if ($this->userExists($user->getUsername())) {
                $data['user_id'] = $this->getUser($user->getUsername())->getIdentifier();
            } else {
                $conn->insert(DBUserManager::$dashboardUsersTable, ['name' => $user->getUsername()]);
                $user->setIdentifier($conn->lastInsertId());
                $data['user_id'] = $user->getIdentifier();
            }

            if ($paneOrDashlet instanceof Pane) {
                $data['dashboard_id'] = $newIdentifier ?: $paneOrDashlet->getPaneId();
                $data['write_access'] = $user->hasWriteAccess() || $user->hasWriteAccess($paneOrDashlet) ? 'y' : 'n';
                $condition = [
                    'dashboard_id = ?'  => $previousId,
                    'user_id = ?'       => $user->getIdentifier()
                ];

                if (! $user->isRemoved()) {
                    $data['removed'] = 'n';
                }

                if (! $paneOrDashlet->hasMember($user->getUsername())) {
                    $data['ctime'] = time();
                } else {
                    $data['mtime'] = time();
                }
            } else {
                $data['dashlet_id'] = $newIdentifier ?: $paneOrDashlet->getDashletId();
                $condition = [
                    'dashlet_id = ?' => $previousId,
                    'user_id = ?'    => $user->getIdentifier()
                ];
            }

            if (! $paneOrDashlet->hasMember($user->getUsername())) {
                if ($user->getUsername() === $this->getAuthUser()->getUsername()) {
                    $data['owner'] = 'y';
                }

                $conn->insert($paneOrDashlet->getTableMembership(), $data);
            } else {
                $conn->update($paneOrDashlet->getTableMembership(), $data, $condition);
            }
        }

        $this->manageRolesAndGroupMembers($paneOrDashlet);

        if ($paneOrDashlet instanceof Pane) {
            $this->initDashboardUsers();
            foreach ($paneOrDashlet->getDashlets() as $dashlet) {
                $dashlet
                    ->setPane($paneOrDashlet)
                    ->setAdditional('with_users', $paneOrDashlet->getAdditional('with_users'));
                $this->savePaneDashletMembership($dashlet);
                $this->manageRolesAndGroupMembers($dashlet);
            }
        }

        return $this;
    }

    /**
     * Get some details of the creator|owner of the given widget
     *
     * @param DashboardHome|Pane|Dashlet $widget
     *
     * @return mixed
     */
    public static function getWidgetOwnerDetail($widget)
    {
        $uniqueId = null;
        $query = (new Select())
            ->columns('du.name, member.type')
            ->from(DBUserManager::$dashboardUsersTable . ' du')
            ->join($widget->getTableMembership() . ' member', 'member.user_id = du.id');

        switch ($widget) {
            case $widget instanceof DashboardHome:
                $uniqueId = $widget->getIdentifier();
                $query
                    ->columns('member.disabled')
                    ->join(DashboardHome::TABLE . ' dh', 'member.home_id = dh.id');

                break;
            case $widget instanceof Pane:
                $uniqueId = $widget->getPaneId();
                $query
                    ->columns('member.write_access, member.ctime, member.mtime')
                    ->join(Pane::TABLE . ' dh', 'member.dashboard_id = dh.id');

                break;
            case $widget instanceof Dashlet:
                $uniqueId = $widget->getDashletId();
                $query->join(Dashlet::TABLE . ' dh', 'member.dashlet_id = dh.id');

                break;
        }

        return DashboardHome::getConn()->select($query->where([
            'member.owner = ?'  => 'y',
            'dh.id = ?'         => $uniqueId
        ]))->fetch();
    }

    /**
     * Migrate all dashboards from the given INI to database
     *
     * @param string $configFile
     */
    public function migrateFromIni($configFile)
    {
        if (is_dir($configFile) || ! file_exists($configFile)) {
            throw new NotFoundError('The provided path is either a directory or does not exist');
        }

        $config = Config::fromIni($configFile);
        if ($config->isEmpty()) {
            return;
        }

        $username = basename(dirname($configFile));
        $this->setAuthUser(new DBUser($username));

        $home = $this->initAndGetDefaultHome();
        $this->activateHome($home);
        if ($home->isDisabled()) {
            Logger::error(
                'Dashboard home "%s" is disabled and cannot be edited. Please enable it first',
                $home->getLabel()
            );
            return;
        }

        $home->loadUserDashboards();
        foreach ($config as $key => $part) {
            if (strpos($key, '.') === false) { // Panes
                $counter = 1;
                $pane = $key;
                while ($home->hasPane($pane)) {
                    $pane = $key . $counter++;
                }

                $pane = (new Pane($pane))
                    ->setUserWidget()
                    ->setType(Dashboard::PRIVATE_DS)
                    ->setName($part->get('title', $pane));
                $home->addPane($pane);
            } else { // Dashlets
                list($pane, $dashlet) = explode('.', $key, 2);
                if (! $home->hasPane($pane)) {
                    continue;
                }

                /** @noinspection PhpUnhandledExceptionInspection */
                $pane = $home->getPane($pane);

                $newDashelt = $dashlet;
                $counter = 1;
                while($pane->hasDashlet($newDashelt)) {
                    $newDashelt = $dashlet . $counter++;
                }

                $dashlet = new Dashlet($part->get('title', $newDashelt), $part->get('url'), $pane);
                $dashlet
                    ->setUserWidget()
                    ->setOwner($username)
                    ->setName($newDashelt);

                /** @noinspection PhpUnhandledExceptionInspection */
                $pane->addDashlet($dashlet);
            }
        }

        $this->manageHome($home);
    }
}
