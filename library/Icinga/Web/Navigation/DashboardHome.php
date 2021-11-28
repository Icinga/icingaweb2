<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Common\DashboardManager;
use Icinga\Common\DBUserManager;
use Icinga\Common\Database;
use Icinga\Common\Relation;
use Icinga\DBUser;
use Icinga\Exception\NotFoundError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Widget\Dashboard;
use ipl\Orm\Query;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use ipl\Web\Url;

/**
 * DashboardHome loads all the panes belonging to the actually selected Home,
 *
 * along with their dashlets.
 */
class DashboardHome extends NavigationItem
{
    use Database;
    use Relation;
    use DBUserManager;

    /**
     * Name of the default home
     *
     * @var string
     */
    const DEFAULT_HOME = 'Default Home';

    /**
     * A Home where all collected dashlets provided by modules are
     *
     * being presented in a special view
     *
     * @var string
     */
    const AVAILABLE_DASHLETS = 'Available Dashlets';

    /**
     * A Home where all shared dashboards with this user are
     *
     * displayed in a dedicated view
     *
     * @var string
     */
    const SHARED_DASHBOARDS = 'Shared Dashboards';

    /**
     * Database table name
     *
     * @var string
     */
    const TABLE = 'dashboard_home';

    /**
     * Non-editable default homes, reserved exclusively for internal use only
     *
     * @var string[]
     */
    const DEFAULT_HOME_ENUMS = [
        self::DEFAULT_HOME,
        self::AVAILABLE_DASHLETS,
        self::SHARED_DASHBOARDS
    ];

    /**
     * Shared database connection
     *
     * @var Connection
     */
    public static $conn;

    /**
     * Database table home user membership
     *
     * @var string
     */
    protected $tableMembership = 'home_member';

    /**
     * Whether this home is active
     *
     * @var bool
     */
    protected $active;

    /**
     * An array of @see Pane belongs to this home
     *
     * @var Pane[]
     */
    private $panes = [];

    /**
     * A flag whether this home is disabled
     *
     * Affects only system dashboards
     *
     * @var bool
     */
    private $disabled;

    /**
     * A user this home belongs to
     *
     * @var string
     */
    private $owner;

    /**
     * This home's unique identifier
     *
     * @var int
     */
    private $identifier;

    /**
     * A type of this dashboard
     *
     * @var string
     */
    private $type = Dashboard::SYSTEM;

    public function init()
    {
        if ($this->getName() !== self::DEFAULT_HOME && ! $this->isDisabled()) {
            $this->setUrl(Url::fromPath(Dashboard::BASE_ROUTE . '/home', ['home' => $this->getName()]));
        } else {
            // Set default url to false when this home has been disabled, so it
            // doesn't show up as a drop-down menu under the navigation bar
            $this->loadWithDefaultUrl(false);
        }

        $this->loadMembers();
        $this->resolveRoleGroupMembers();
    }

    public function loadMembers()
    {
        // Prevents from being stuck in an infinite loop, when calling self::getConn()
        if (! $this->getIdentifier()) {
            return;
        }

        $members = self::getConn()->select((new Select())
            ->columns('user.id, user.name')
            ->from($this->getTableMembership() . ' dm')
            ->join(self::TABLE . ' dh', 'dm.home_id = dh.id')
            ->join(DBUserManager::$dashboardUsersTable . ' user', 'user.id = dm.user_id')
            ->where(['dh.id = ?' => $this->getIdentifier()]));

        $users = [];
        foreach ($members as $member) {
            $member = (new DBUser($member->name))->setIdentifier($member->id);

            $users[$member->getUsername()] = $member;
        }

        $this->setMembers($users);
    }

    /**
     * Get Database connection
     *
     * This is needed because we don't want to always initiate a new DB connection when calling $this->getDb().
     * And as we are using PDO transactions to manage the dashboards, this wouldn't work if $this->getDb()
     * is called over again after a transaction has been initiated
     *
     * @return Connection
     */
    public static function getConn()
    {
        if (self::$conn === null) {
            self::$conn = (new self(self::DEFAULT_HOME))->getDb();
        }

        return self::$conn;
    }

    /**
     * Generate the sha1 hash of the provided string
     *
     * @param string $name
     *
     * @return string
     */
    public static function getSHA1($name)
    {
        return sha1($name, true);
    }

    /**
     * Set whether this home is active
     *
     * DB dashboards are loaded only when this home has been activated
     *
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active = true)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get whether this home is active
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set the type of this home
     *
     * @param $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the type of this home
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set this home's unique identifier
     *
     * @param  int $id
     *
     * @return $this
     */
    public function setIdentifier($id)
    {
        $this->identifier = (int) $id;

        return $this;
    }

    /**
     * Get this home's identifier
     *
     * @return int
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set disabled state for system homes
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled = true)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled state for this home
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set the owner of this widget
     *
     * @param $owner
     *
     * @return $this
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get the owner of this widget
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Load system panes provided by all enabled modules which doesn't
     * belong to any dashboard home
     *
     * @return $this
     */
    public function loadSystemDashboards()
    {
        // Standalone system panes are being loaded only in "Default Home" dashboard home
        if ($this->getName() !== self::DEFAULT_HOME) {
            return $this;
        }

        // Skip if this home is either disabled or inactive
        if (! $this->getActive() || $this->isDisabled()) {
            return $this;
        }

        $navigation = new Navigation();
        $navigation->load('dashboard-pane');

        $user = $this->getAuthUser();
        $panes = [];
        /** @var DashboardPane $dashboardPane */
        foreach ($navigation as $dashboardPane) {
            $paneId = self::getSHA1($user->getUsername() . self::DEFAULT_HOME . $dashboardPane->getName());
            $pane = new Pane($dashboardPane->getName());
            $pane
                ->setOwner($user->getUsername())
                ->setHome($this)
                ->setPaneId($paneId)
                ->setTitle($dashboardPane->getLabel());

            $dashlets = [];
            /** @var NavigationItem $dashlet */
            foreach ($dashboardPane->getChildren() as $dashlet) {
                $dashletId = self::getSHA1(
                    $user->getUsername() . self::DEFAULT_HOME . $pane->getName() . $dashlet->getName()
                );

                $newDashlet = new Dashlet($dashlet->getLabel(), $dashlet->getUrl()->getRelativeUrl(), $pane);
                $newDashlet
                    ->setOwner($user->getUsername())
                    ->setName($dashlet->getName())
                    ->setDashletId($dashletId);

                $dashlets[$dashlet->getName()] = $newDashlet;
            }

            $pane->addDashlets($dashlets);
            $panes[$pane->getName()] = $pane;
        }

        $this->mergePanes($panes);

        return $this;
    }

    /**
     * Load user specific dashboards and dashlets from the DB and
     * merge them to the system dashboards
     *
     * @return $this
     */
    public function loadUserDashboards()
    {
        // Skip if this home is either disabled or inactive
        if (! $this->getActive() || $this->isDisabled()) {
            return $this;
        }

        $dashboards = self::getConn()->select((new Select())
            ->columns('d.*, COALESCE(do.priority, 0) AS `order`, dm.type, dm.ctime, dm.mtime, dm.owner, dm.write_access')
            ->from(Pane::TABLE . ' d')
            ->join('dashboard_member dm', ['dm.dashboard_id = d.id', 'dm.removed = ?' => 'n'])
            ->join(DBUserManager::$dashboardUsersTable . ' du', 'du.id = dm.user_id')
            ->joinLeft('dashboard_order do', 'd.id = do.dashboard_id')
            ->where([
                'd.home_id = ?' => $this->getIdentifier(),
                'du.name = ?'   => $this->getAuthUser()->getUsername(),
            ])
            ->orderBy('`order`'));

        $panes = [];
        foreach ($dashboards as $dashboard) {
            $pane = new Pane($dashboard->name);
            $pane->setUserWidget();
            $pane->setType($dashboard->type ?: Dashboard::SYSTEM);
            $pane
                ->setHome($this)
                ->setPaneId($dashboard->id)
                ->setTitle($dashboard->label)
                ->setMtime($dashboard->mtime)
                ->setCtime($dashboard->ctime)
                ->setOrder($dashboard->order);

            if ($dashboard->owner === 'y') {
                $pane->setOwner($this->getAuthUser()->getUsername());
            } else {
                $owner = DashboardManager::getWidgetOwnerDetail($pane);
                $pane->setOwner(! $owner ?: $owner->name);
            }

            $dashlets = self::getConn()->select((new Select())
                ->columns('ds.*, COALESCE(dso.priority, 0) AS `order`, du.name as owner, dsm.type')
                ->from(Dashlet::TABLE . ' ds')
                ->join('dashlet_member dsm', 'dsm.dashlet_id = ds.id')
                ->join(DBUserManager::$dashboardUsersTable . ' du', 'du.id = dsm.user_id')
                ->joinLeft('dashlet_order dso', 'ds.id = dso.dashlet_id')
                ->where([
                    'du.name = ?'           => $this->getAuthUser()->getUsername(),
                    'ds.dashboard_id = ?'   => $pane->getPaneId()
                ])->orderBy('`order`'));

            $paneDashlets = [];
            foreach ($dashlets as $dashletData) {
                $dashlet = new Dashlet($dashletData->label, $dashletData->url, $pane);
                $dashlet->setUserWidget();
                $dashlet
                    ->setName($dashletData->name)
                    ->setType($dashletData->type)
                    ->setOwner($dashletData->owner)
                    ->setDashletId($dashletData->id)
                    ->setOrder($dashletData->order);

                $paneDashlets[$dashlet->getName()] = $dashlet;
            }

            $pane->addDashlets($paneDashlets);
            $panes[$pane->getName()] = $pane;
        }

        $this->mergePanes($panes);

        return $this;
    }

    /**
     * Load a pane from the DB, which overwrites a system panes, if any
     *
     * @return $this
     */
    public function loadOverridingDashboards()
    {
        foreach ($this->getPanes() as $pane) {
            if ($pane->isUserWidget()) {
                continue;
            }

            $this->overridePane($pane);
        }

        return $this;
    }

    /**
     * Set this home's dashboards
     *
     * @param Pane|Pane[]|Navigation $panes
     */
    public function setPanes($panes)
    {
        if ($panes instanceof Navigation) {
            $newPanes = [];
            $user = $this->getAuthUser();

            /** @var DashboardPane $pane */
            foreach ($panes as $pane) {
                $newPane = new Pane($pane->getName());
                $newPane->setOwner($user->getUsername());
                $newPane
                    ->setHome($this)
                    ->setTitle($pane->getLabel())
                    ->setPaneId(self::getSHA1($user->getUsername() . $this->getName() . $pane->getName()));

                // Cast array dashelts to NavigationItem
                $pane->setChildren($pane->getAttribute('dashlets'));
                $pane->setAttribute('dashlets', null);

                $dashlets = [];
                /** @var NavigationItem $dashlet */
                foreach ($pane->getChildren() as $dashlet) {
                    $newDashlet = new Dashlet($dashlet->getLabel(), $dashlet->getUrl()->getRelativeUrl(), $newPane);
                    $newDashlet
                        ->setName($dashlet->getName())
                        ->setDashletId(self::getSHA1(
                            $user->getUsername() . $this->getName() . $pane->getName() . $dashlet->getName()
                        ));

                    $dashlets[$dashlet->getName()] = $newDashlet;
                }

                $newPane->addDashlets($dashlets);
                $newPanes[$pane->getName()] = $newPane;
            }

            $panes = $newPanes;
        } elseif (! is_array($panes)) {
            $panes = [$panes->getName() => $panes];
        }

        $this->panes = $panes;

        return $this;
    }

    /**
     * Merge panes with existing panes
     *
     * @param Pane[] $panes
     *
     * @return $this
     */
    public function mergePanes(array $panes)
    {
        // Skip if this home is either disabled or inactive
        if (! $this->getActive() || $this->isDisabled()) {
            return $this;
        }

        foreach ($panes as $pane) {
            if ($pane->isUserWidget() && empty($pane->getPaneId())) {
                throw new ProgrammingError('Custom pane "%s" does not have an identifier', $pane->getName());
            }

            $currentPane = null;
            if ($this->hasPane($pane->getName())) {
                $currentPane = $this->getPane($pane->getName());

                // Check whether the user has cloned system pane w/o modifying it
                if ($pane->isUserWidget() && $pane->getType() === Dashboard::SYSTEM && ! $currentPane->isUserWidget()) {
                    if ($pane->getTitle() === $currentPane->getTitle() && ! $pane->hasDashlets()) {
                        // Cleaning up cloned system panes from the DB
                        if ($pane->isOverridingWidget() || $pane->getOrder() === $currentPane->getOrder()) {
                            $this->removePane($pane);

                            continue;
                        }
                    }
                }

                $currentPane
                    ->setTitle($pane->getTitle())
                    ->setOrder($pane->getOrder())
                    ->setPaneId($pane->getPaneId())
                    ->addDashlets($pane->getDashlets())
                    ->setUserWidget($pane->isUserWidget())
                    ->setOverride($pane->isOverridingWidget());
            }

            $this->overridePane($pane);
            if ($currentPane) {
                continue;
            }

            $this->addPane($pane);
        }

        return $this;
    }

    /**
     * Get this home's dashboard panes
     *
     * @param bool $skipDisabled Whether to skip disabled panes
     *
     * @return Pane[]
     */
    public function getPanes(bool $skipDisabled = false)
    {
        // As the panes can also be added individually afterwards, it might be the case that order
        // priority gets mixed up, so we have to sort things here before being able to render them
        uasort($this->panes, function (Pane $x, Pane $y) {
            return $x->getOrder() - $y->getOrder();
        });

        return ! $skipDisabled ? $this->panes : array_filter(
            $this->panes, function ($pane) {
                return ! $pane->isDisabled();
            }
        );
    }

    /**
     * Return the pane with the provided name
     *
     * @param string $name The name of the pane to return
     *
     * @return Pane
     * @throws ProgrammingError
     */
    public function getPane($name)
    {
        if (! $this->hasPane($name)) {
            throw new ProgrammingError('Trying to retrieve invalid dashboard pane "%s"', $name);
        }

        return $this->panes[$name];
    }

    /**
     * Modify the given pane's properties if it is a cloned or system pane
     *
     * @param Pane $pane
     */
    protected function overridePane(Pane $pane)
    {
        // Check whether the pane is a system or cloned pane
        if (! $pane->isUserWidget() || $pane->getType() === Dashboard::SYSTEM) {
            $overridingPane = self::getConn()->select((new Select())
                ->columns('*')
                ->from(Pane::DASHBOARD_OVERRIDE)
                ->where([
                    'user_id = ?'       => $this->getAuthUser()->getIdentifier(),
                    'dashboard_id = ?'  => $pane->getPaneId()
                ]))->fetch();

            if ($overridingPane) {
                // Remove the custom pane if label is null|rolled back to it's org value and is not disabled
                if (
                    ! (bool) $overridingPane->disabled
                    && (
                        ! $overridingPane->label
                        || $overridingPane->label === $pane->getTitle()
                    ) && ! $pane->isUserWidget()
                ) {
                    $pane->setOverride(true);
                    $this->removePane($pane);
                } else {
                    $pane->setUserWidget();
                    $pane->setOverride(true);
                    $pane->setDisabled($overridingPane->disabled);

                    if ($overridingPane->label) {
                        $pane->setTitle($overridingPane->label);
                    }
                }
            }
        }

        foreach ($pane->getDashlets() as $dashlet) {
            if (! $dashlet->isUserWidget()) {
                $overridingDashlet = self::getConn()->select((new Select())
                    ->columns('*')
                    ->from(Dashlet::OVERRIDING_TABLE)
                    ->where([
                        'user_id = ?'       => $this->getAuthUser()->getIdentifier(),
                        'dashlet_id = ?'    => $dashlet->getDashletId()
                    ]))->fetch();

                if ($overridingDashlet) {
                    // Remove the overriding dashlet if label & url are null|rolled back
                    // to their org value and is not disabled
                    if (
                        ! (bool) $overridingDashlet->disabled
                        && (
                            ! $overridingDashlet->label
                            || $overridingDashlet->label === $dashlet->getTitle()
                        ) && (
                            ! $overridingDashlet->url
                            || $dashlet->getUrl()->matches($overridingDashlet->url)
                        )
                    ) {
                        $dashlet->setOverride(true);
                        $pane->removeDashlet($dashlet);
                    } else {
                        $dashlet->setUserWidget();
                        $dashlet->setOverride(true);
                        $dashlet->setDisabled($overridingDashlet->disabled);

                        if ($overridingDashlet->url) {
                            $dashlet->setUrl($overridingDashlet->url);
                        }

                        if ($overridingDashlet->label) {
                            $dashlet->setTitle($overridingDashlet->label);
                        }
                    }
                }
            }
        }
    }

    /**
     * Checks if this home has any panes
     *
     * @return bool
     */
    public function hasPanes()
    {
        return ! empty($this->panes);
    }

    /**
     * Get whether the given pane exist
     *
     * @param string  $pane
     *
     * @return bool
     */
    public function hasPane($pane)
    {
        return array_key_exists($pane, $this->panes);
    }

    /**
     * Add a new pane to this home
     *
     * @param Pane|string $pane
     *
     * @return $this
     */
    public function addPane($pane)
    {
        if (! $pane instanceof Pane) {
            $pane = new Pane($pane);
            $pane->setTitle($pane->getName());
        }

        if (
            ! $pane->isUserWidget()
            && $this->hasPane($pane->getName())
            && $this->getPane($pane->getName())->isUserWidget()
        ) {
            return $this;
        }

        $pane->setHome($this);
        $this->panes[$pane->getName()] = $pane;

        return $this;
    }

    /**
     * Remove a specific pane form this home
     *
     * @param Pane|string $pane
     *
     * @return $this
     */
    public function removePane($pane)
    {
        if (! $pane instanceof Pane) {
            if (! $this->hasPane($pane)) {
                throw new ProgrammingError('Trying to remove invalid dashboard pane "%s"', $pane);
            }

            $pane = $this->getPane($pane);
        }

        $purgeFromDB = false;
        if ($pane->getType() === Dashboard::PUBLIC_DS || $pane->getType() === Dashboard::SHARED) {
            $paneUsers = $pane->getAdditional('unshared_users');
            $homeUsers = $this->getAdditional('unshared_users');
            $allUsers = [];
            if ($paneUsers instanceof DBUser) {
                $purgeFromDB = $paneUsers->getUsername() === '*';
            } else {
                $allUsers = $paneUsers;
            }

            if (! $purgeFromDB && $homeUsers instanceof DBUser) {
                $purgeFromDB = $homeUsers->getUsername() === '*';
            } else {
                $allUsers = array_unique(array_merge($allUsers, $homeUsers));
            }

            if (! $purgeFromDB) {
                $purgeFromDB = (bool) array_filter($allUsers, function ($DBUser) {
                    return $DBUser->getUsername() === '*';
                });
            }
        }

        $conn = self::getConn();
        if ($purgeFromDB || $pane->isOverridingWidget() || $pane->getType() === Dashboard::PRIVATE_DS) {
            if (! $pane->isOverridingWidget()) {
                // Due to overriding dashlet widgets we have to call this explicitly even though we have
                // a composition between dashboard and dashlet table which might not be cleaned up properly
                // in dashlet_overriding due to not having a relationship there
                $pane->removeDashlets();

                $conn->delete(Pane::TABLE, [
                    'id = ?'        => $pane->getPaneId(),
                    'home_id = ?'   => $this->getIdentifier()
                ]);
            } else {
                // Overriding widgets
                $conn->delete(Pane::DASHBOARD_OVERRIDE, [
                    'user_id = ?'       => $this->getAuthUser()->getIdentifier(),
                    'dashboard_id = ?'  => $pane->getPaneId()
                ]);
            }
        } elseif ($pane->getType() === Dashboard::PUBLIC_DS || $pane->getType() === Dashboard::SHARED) {
            if ($pane->getType() === Dashboard::SHARED && $pane->getOwner() === $this->getAuthUser()->getUsername()) {
                $conn->update($pane->getTableMembership(), ['removed' => 'y'], [
                    'dashboard_id = ?'  => $pane->getPaneId(),
                    'user_id = ?'       => $this->getAuthUser()->getIdentifier()
                ]);
            } else {
                $conn->delete($pane->getTableMembership(), [
                    'dashboard_id = ?'  => $pane->getPaneId(),
                    'user_id = ?'       => $this->getAuthUser()->getIdentifier()
                ]);
            }
        } elseif (! $pane->isDisabled() && ! $this->isDisabled()) {
            // User is going to disable a system pane
            $conn->insert(Pane::DASHBOARD_OVERRIDE, [
                'dashboard_id'  => $pane->getPaneId(),
                'user_id'       => $this->getAuthUser()->getIdentifier(),
                'disabled'      => 1
            ]);
        }

        return $this;
    }

    /**
     * Remove all panes from this home, unless you specified the panes
     *
     * @param Pane[] $panes
     *
     * @return $this
     */
    public function removePanes(array $panes = [])
    {
        if (empty($panes)) {
            $panes = $this->getPanes();
        }

        foreach ($panes as $pane) {
            $this->removePane($pane);
        }

        return $this;
    }

    /**
     * Get an array with pane name=>title format used for combobox
     *
     * @param bool $onlyShared Get only shared dashboard names
     *
     * @return array
     */
    public function getPaneKeyTitleArray(bool $onlyShared = false)
    {
        $panes = [];
        foreach ($this->getPanes() as $pane) {
            if (($onlyShared && $pane->getType() !== Dashboard::SHARED) || $pane->isDisabled()) {
                continue;
            }

            $panes[$pane->getName()] = $pane->getTitle();
        }

        return $panes;
    }

    /**
     * Move the given dashboard pane up or down in order
     *
     * @param Pane $orgPane
     * @param int  $position
     *
     * @return $this
     */
    public function reorderPane(Pane $orgPane, $position)
    {
        if (! $this->hasPane($orgPane->getName())) {
            throw new NotFoundError('No dashboard pane called "%s" found', $orgPane->getName());
        }

        $conn = self::getConn();
        $panes = array_values($this->getPanes());
        array_splice($panes, array_search($orgPane->getName(), array_keys($this->getPanes())), 1);
        array_splice($panes, $position, 0, [$orgPane]);

        foreach ($panes as $key => $pane) {
            $filter = [
                'user_id = ?'       => $this->getAuthUser()->getIdentifier(),
                'dashboard_id = ?'  => $pane->getPaneId()
            ];

            $order = $key + 1;
            if (! $pane->isUserWidget() || $pane->getOrder() < 1) {
                if (! $this->panePersists($pane)) {
                    $conn->insert(Pane::TABLE, [
                        'id'        => $pane->getPaneId(),
                        'home_id'   => $this->getIdentifier(),
                        'name'      => $pane->getName(),
                        'label'     => $pane->getTitle()
                    ]);
                }

                $pane->loadMembers();
                if (! $pane->hasMember($this->getAuthUser()->getUsername())) {
                    $conn->insert($pane->getTableMembership(), [
                        'dashboard_id'  => $pane->getPaneId(),
                        'user_id'       => $this->getAuthUser()->getIdentifier(),
                        'type'          => Dashboard::SYSTEM,
                        'owner'         => 'y'
                    ]);
                }

                $conn->insert('dashboard_order', [
                    'priority'      => $order,
                    'user_id'       => $this->getAuthUser()->getIdentifier(),
                    'dashboard_id'  => $pane->getPaneId()
                ]);
            } else {
                $conn->update('dashboard_order', ['priority' => $order], $filter);
            }
        }

        return $this;
    }

    /**
     * Check whether the given pane exists in the DB and isn't member of this user
     *
     * @param ?Pane $pane
     *
     * @return bool
     */
    public function panePersists(Pane $pane = null)
    {
        $columns = new Expression('1');
        $filter = ['home_id = ?' => $this->getIdentifier()];
        $query = (new Select())->from(Pane::TABLE);
        if ($pane !== null) {
            $columns = 'id';
            $filter = ['id = ?' => $pane->getPaneId()];
        } else {
            $query->join(DashboardHome::TABLE, 'dashboard_home.id = home_id');
        }

        $query->columns($columns)->where($filter);
        $result = self::getConn()->select($query)->fetch();
        if ($pane && $result) {
            $pane->setPaneId($result->id);

            return true;
        }

        return $result !== false;
    }

    /**
     * Collect all dashlets provided by modules in the config script
     *
     * @return Dashlet[]
     */
    public function loadProvidedDashlets()
    {
        // Skip if this home isn't active or this name doesn't equal the preserved name
        if ($this->getName() !== self::AVAILABLE_DASHLETS || ! $this->getActive()) {
            return [];
        }

        $dashlets = [];
        $moduleManager = Icinga::app()->getModuleManager();
        foreach ($moduleManager->getLoadedModules() as $module) {
            if (
                empty($module->getDashlets())
                || ! $this->getAuthUser()->can($moduleManager::MODULE_PERMISSION_NS . $module->getName())
            ) {
                continue;
            }

            foreach ($module->getDashlets() as $name => $properties) {
                $dashlet = new Dashlet($name, $properties['url']);
                $dashlet->setDashletId(self::getSHA1($module->getName() . $dashlet->getName()));
                // Don't load when this dashlet already exists in the DB, i.e. it is already being used somewhere
                if (Pane::dashletPersists($dashlet)) {
                    continue;
                }

                foreach ($properties as $key => $property) {
                    $method = 'set' . ucfirst($key);
                    if (empty($property) || ! method_exists($dashlet, $method)) {
                        continue;
                    }

                    call_user_func([$dashlet, $method], $property);
                }

                $dashlets[$module->getName()][$dashlet->getName()] = $dashlet;
            }
        }

        return $dashlets;
    }

    /**
     * Get a list of shared dashboards
     *
     * @param Query $query
     *
     * @return Pane[]
     */
    public function getSharedPanes(Query $query)
    {
        // Skip if this home isn't active or this name doesn't equal the preserved name
        if ($this->getName() !== self::SHARED_DASHBOARDS || ! $this->getActive()) {
            return [];
        }

        $panes = [];
        foreach ($query->execute() as $dashboard) {
            $home = new self($dashboard->dashboard_home->name);
            $home
                ->setLabel($dashboard->dashboard_home->label)
                ->setIdentifier($dashboard->dashboard_home->id);

            $pane = new Pane($dashboard->name);
            $pane->setUserWidget();
            $pane->setType(Dashboard::SHARED);
            $pane
                ->setHome($home)
                ->setPaneId($dashboard->id)
                ->setTitle($dashboard->label)
                ->setMtime($dashboard->dashboard_member->mtime)
                ->setCtime($dashboard->dashboard_member->ctime);

            if ($dashboard->dashboard_member->owner === 'y') {
                $pane->setOwner($dashboard->dashboard_member->dashboard_user->name);
            } else {
                $owner = DashboardManager::getWidgetOwnerDetail($pane);
                $pane->setOwner($owner ? $owner->name : $this->getAuthUser()->getUsername());
            }

            $panes[$pane->getName()] = $pane;
        }

        return $panes;
    }

    /**
     * Resolve dashboard panes which have been assigned to groups or roles down to user level
     *
     * When the currently logged-in user doesn't have the appropriate role or
     * is not part of appropriate group no resolution will be performed
     *
     * @return $this
     */
    public function resolveRoleGroupMembers()
    {
        // Prevents from being stuck in an infinite loop, when calling self::getConn()
        if (! $this->getIdentifier()) {
            return $this;
        }

        $conn = self::getConn();
        $unionQuery = (new Select())
            ->columns('d.name, d.id, dg.name AS `group`, NULL AS `role`')
            ->from(Pane::TABLE . ' d')
            ->join(Dashboard::GROUP_ROLE_TABLE . ' grm', 'grm.dashboard_id = d.id')
            ->join(DBUserManager::$dashboardGroup . ' dg', 'dg.id = grm.group_id')
            ->where(['d.home_id = ?' => $this->getIdentifier()])
            ->groupBy(['d.id', '`role`'])
            ->unionAll(
                (new Select())
                    ->columns('d.name, d.id, NULL AS `group`, dr.role AS `role`')
                    ->from(Pane::TABLE . ' d')
                    ->join(Dashboard::GROUP_ROLE_TABLE . ' grm', 'grm.dashboard_id = d.id')
                    ->join(DBUserManager::$dashboardRole . ' dr', 'grm.role_id = dr.id')
                    ->where(['d.home_id = ?' => $this->getIdentifier()])
                    ->groupBy(['d.id', '`role`'])
            );

        $panes = $conn->select((new Select())
            ->columns('*')
            ->from(['dashboard' => $unionQuery]));

        foreach ($panes as $pane) {
            // Skip if this user isn't assigned to the role
            if ($pane->role && ! $this->getAuthUser()->hasRole($pane->role)) {
                continue;
            }

            // Skip if this user isn't member of the group
            if ($pane->group && ! $this->getAuthUser()->isMemberOf($pane->group)) {
                continue;
            }

            $pane = (new Pane($pane->name))->setPaneId($pane->id);
            $pane->loadMembers();
            if ($pane->hasMember($this->getAuthUser()->getUsername())) {
                continue;
            }

            $ownerDetail = DashboardManager::getWidgetOwnerDetail($pane);
            if (! $ownerDetail) {
                Logger::info(
                    'There is a corrupted dashboard pane in the database with the identity "%s" which doesn\'t'
                    . ' have an owner. This will be purged automatically!',
                    bin2hex($pane->getPaneId())
                );

                $pane->setType(Dashboard::PRIVATE_DS);
                $this->removePane($pane);

                continue;
            }

            $conn->insert($pane->getTableMembership(), [
                'dashboard_id'  => $pane->getPaneId(),
                'user_id'       => $this->getAuthUser()->getIdentifier(),
                'write_access'  => $ownerDetail->write_access,
                'type'          => $ownerDetail->type,
                'ctime'         => $ownerDetail->ctime,
                'mtime'         => $ownerDetail->mtime
            ]);
        }

        return $this;
    }
}
