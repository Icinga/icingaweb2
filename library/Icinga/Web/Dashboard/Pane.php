<?php

/* Icinga Web 2 | (c) 2013-2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard;

use DateTime;
use DateTimeZone;
use Icinga\Application\Logger;
use Icinga\Common\DashboardManager;
use Icinga\Common\DBUserManager;
use Icinga\Common\Relation;
use Icinga\DBUser;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Widget\Dashboard;
use ipl\Sql\Select;

/**
 * A Dashboard pane, displaying different dashlets
 */
class Pane
{
    use UserWidget;
    use Relation;
    use DBUserManager;

    /**
     * Database table name
     *
     * @var string
     */
    const TABLE = 'dashboard';

    /**
     * Database table name for overriding dashboards
     *
     * @var string
     */
    const DASHBOARD_OVERRIDE = 'dashboard_override';

    /**
     * DateTime format being used when rendering shared dashboards last modify time
     *
     * @var string
     */
    const DATETIME_FORMAT = 'M dS, Y';

    protected $tableMembership = 'dashboard_member';

    /**
     * The not translatable name of this pane
     *
     * @var string
     */
    private $name;

    /**
     * The title of this pane, as displayed in the dashboard tabs
     *
     * @var string
     */
    private $title;

    /**
     * An array of @see Dashlet that are displayed in this pane
     *
     * @var Dashlet[]
     */
    private $dashlets = [];

    /**
     * Dashboard home of this pane
     *
     * @var DashboardHome
     */
    private $home;

    /**
     * Unique identifier of this pane
     *
     * @var string
     */
    private $paneId;

    /**
     * The priority order of this pane
     *
     * @var int
     */
    private $order;

    /**
     * Creation time of this dashboard
     *
     * @var int|float
     */
    private $ctime;

    /**
     * Last modified time of this dashboard
     *
     * @var int|float
     */
    private $mtime;

    /**
     * Create a new pane
     *
     * @param string $name The pane to create
     */
    public function __construct($name)
    {
        $this->name  = $name;
        $this->title = $name;
    }

    public function loadMembers()
    {
        $conn = DashboardHome::getConn();
        $members = $conn->select((new Select())
            ->columns('user.id, user.name, member.write_access, member.removed')
            ->from($this->getTableMembership() . ' member')
            ->join(self::TABLE . ' pane', 'member.dashboard_id = pane.id')
            ->join(DBUserManager::$dashboardUsersTable . ' user', 'user.id = member.user_id')
            ->where(['pane.id = ?' => $this->getPaneId()]));

        $users = [];
        foreach ($members as $member) {
            $member = (new DBUser($member->name))
                ->setIdentifier($member->id)
                ->setRemoved($member->removed === 'y')
                ->setWriteAccess($member->write_access === 'y');
            $users[$member->getUsername()] = $member;
        }

        $this->setMembers($users);
        $this->resolveRoleGroupMembers();
    }

    /**
     * Set the dashboard home of this pane
     *
     * @param  DashboardHome $home
     *
     * @return $this
     */
    public function setHome(DashboardHome $home)
    {
        $this->home = $home;

        return $this;
    }

    /**
     * Get the dashboard home of this pane
     *
     * @return ?DashboardHome
     */
    public function getHome()
    {
        return $this->home;
    }

    /**
     * Set unique identifier of this pane
     *
     * @param  string  $id
     */
    public function setPaneId($id)
    {
        $this->paneId = $id;

        return $this;
    }

    /**
     * Get the unique identifier of this pane
     *
     * @return string
     */
    public function getPaneId()
    {
        return $this->paneId;
    }

    /**
     * Get the priority order of this pane
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the priority order of this pane
     *
     * @param int $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = (int) $order;

        return $this;
    }

    /**
     * Set the name of this pane
     *
     * @param string  $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the name of this pane
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the title of this pane
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title !== null ? $this->title : $this->getName();
    }

    /**
     * Overwrite the title of this pane
     *
     * @param string $title     The new title to use for this pane
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get creation time of this pane
     *
     * @return string
     */
    public function getCtime()
    {
        if (! $this->ctime) {
            return '';
        }

        $dt = DateTime::createFromFormat('U.u', sprintf('%F', $this->ctime));
        return $dt->setTimezone(new DateTimeZone('UTC'))
            ->format(self::DATETIME_FORMAT);
    }

    /**
     * Set creation time of this pane
     *
     * This is only of use for shared dashboards
     *
     * @param ?int|float $ctime
     *
     * @return $this
     */
    public function setCtime($ctime)
    {
        $this->ctime = $ctime;

        return $this;
    }

    /**
     * Get last modified time of this dashboard
     *
     * @return string
     */
    public function getMtime()
    {
        if (! $this->mtime) {
            return '';
        }

        $dt = DateTime::createFromFormat('U.u', sprintf('%F', $this->mtime));
        return $dt->setTimezone(new DateTimeZone('UTC'))
            ->format(self::DATETIME_FORMAT);
    }

    /**
     * Set last modified time of this dashboard
     *
     * @param ?int|float $mtime
     *
     * @return $this
     */
    public function setMtime($mtime)
    {
        $this->mtime = $mtime;

        return $this;
    }

    /**
     * Return true if a dashlet with the given title exists in this pane
     *
     * @param string $name The title of the dashlet to check for existence
     *
     * @return bool
     */
    public function hasDashlet($name)
    {
        return array_key_exists($name, $this->dashlets);
    }

    /**
     * Checks if the current pane has any dashlets
     *
     * @return bool
     */
    public function hasDashlets()
    {
        return ! empty($this->dashlets);
    }

    /**
     * Return a dashlet with the given name if existing
     *
     * @param string $name       The title of the dashlet to return
     *
     * @return Dashlet            The dashlet with the given title
     * @throws ProgrammingError   If the dashlet doesn't exist
     */
    public function getDashlet($name)
    {
        if ($this->hasDashlet($name)) {
            return $this->dashlets[$name];
        }

        throw new ProgrammingError(
            'Trying to access invalid dashlet: %s',
            $name
        );
    }

    /**
     * Removes the dashlet with the given title if it exists in this pane
     *
     * @param   Dashlet|string $dashlet
     *
     * @return  $this
     */
    public function removeDashlet($dashlet)
    {
        if (! $dashlet instanceof Dashlet) {
            if (! $this->hasDashlet($dashlet)) {
                throw new ProgrammingError('Trying to remove invalid dashlet: %s', $dashlet);
            }

            $dashlet = $this->getDashlet($dashlet);
        }

        if (! $dashlet->getDashletId()) {
            return $this;
        }

        $purgeFromDB = false;
        if ($dashlet->getType() === Dashboard::PUBLIC_DS || $dashlet->getType() === Dashboard::SHARED) {
            $purgeFromDB = (bool) array_filter($dashlet->getAdditional('unshared_users'), function ($DBUser) {
                return $DBUser->getUsername() === '*';
            });
        }

        if (
            $dashlet->isOverridingWidget()
            || $purgeFromDB
            || $dashlet->getType() === Dashboard::PRIVATE_DS
        ) {
            // Overriding dashlet widget
            DashboardHome::getConn()->delete(Dashlet::OVERRIDING_TABLE, [
                'user_id = ?'       => $this->getAuthUser()->getIdentifier(),
                'dashlet_id = ?'    => $dashlet->getDashletId()
            ]);

            // Custom dashlets
            DashboardHome::getConn()->delete(Dashlet::TABLE, [
                'id = ?'            => $dashlet->getDashletId(),
                'dashboard_id = ?'  => $this->getPaneId()
            ]);
        } elseif ($dashlet->getType() === Dashboard::PUBLIC_DS) {
            DashboardHome::getConn()->delete($dashlet->getTableMembership(), [
                'dashlet_id = ?' => $dashlet->getDashletId(),
                'user_id = ?'    => $this->getHome()->getAuthUser()->getIdentifier()
            ]);
        } elseif (! $dashlet->isDisabled() && ! $this->isDisabled()) {
            DashboardHome::getConn()->insert(Dashlet::OVERRIDING_TABLE, [
                'dashlet_id'    => $dashlet->getDashletId(),
                'user_id'       => $this->getAuthUser()->getIdentifier(),
                'disabled'      => 1
            ]);
        }

        return $this;
    }

    /**
     * Removes all or a given list of dashlets from this pane
     *
     * @param array|null $dashlets Optional list of dashlet titles
     *
     * @return Pane $this
     */
    public function removeDashlets(array $dashlets = [])
    {
        if (empty($dashlets)) {
            $dashlets = $this->getDashlets();
        }

        foreach ($dashlets as $dashlet) {
            $this->removeDashlet($dashlet);
        }

        return $this;
    }

    /**
     * Get all dashlets belongs to this pane
     *
     * @param bool $skipDisabled Whether to skip disabled dashlets
     *
     * @return Dashlet[]
     */
    public function getDashlets($skipDisabled = false)
    {
        $dashlets = $this->dashlets;

        if ($skipDisabled) {
            $dashlets = array_filter($this->dashlets, function ($dashlet) {
                return ! $dashlet->isDisabled();
            });
        }

        uasort($dashlets, function (Dashlet $x, Dashlet $y) {
            return $y->getOrder() - $x->getOrder();
        });

        return $dashlets;
    }

    /**
     * Add a dashlet to this pane, optionally creating it if $dashlet is a string
     *
     * If the given dashlet is system and there is already dashlet with same name,
     * it won't be added as custom dashlets have higher priority than system ones
     *
     * @param string|Dashlet $dashlet The dashlet object or title (if a new dashlet will be created)
     * @param string|null $url        An Url to be used when $dashlet is a string type
     *
     * @return $this
     */
    public function addDashlet($dashlet, $url = null)
    {
        if (is_string($dashlet) && $url !== null) {
            $dashlet = new Dashlet($dashlet, $url);
        } elseif (! $dashlet instanceof Dashlet) {
            throw new ConfigurationError('Invalid dashlet added: %s', $dashlet);
        }

        if ($this->hasDashlet($dashlet->getName())) {
            // Custom dashlets always take precedence over system dashlets
            if (! $dashlet->isUserWidget() && $this->getDashlet($dashlet->getName())->isUserWidget()) {
                return $this;
            }
        }

        $dashlet->setPane($this);
        $this->dashlets[$dashlet->getName()] = $dashlet;

        return $this;
    }

    /**
     * Add new dashlets
     *
     * @param Dashlet[] $dashlets
     *
     * @return $this
     */
    public function addDashlets(array $dashlets)
    {
        foreach ($dashlets as $dashlet) {
            $this->addDashlet($dashlet);
        }

        return $this;
    }

    /**
     * Set this pane's dashlets
     *
     * @param Dashlet|Dashlet[] $dashlets
     *
     * @return $this
     */
    public function setDashlets($dashlets)
    {
        if ($dashlets instanceof Dashlet) {
            $dashlets = [$dashlets->getName() => $dashlets];
        }

        $this->dashlets = $dashlets;

        return $this;
    }

    /**
     * Check whether the given dashlet exists in the DB and isn't member of this user
     *
     * @param Dashlet $dashlet
     *
     * @return bool
     */
    public static function dashletPersists(Dashlet $dashlet)
    {
        $conn = DashboardHome::getConn();
        $result = $conn->select((new Select())
            ->columns('id')
            ->from(Dashlet::TABLE)
            ->where(['id = ?' => $dashlet->getDashletId()]))->fetch();

        if ($result) {
            $dashlet->setDashletId($result->id);

            return true;
        }

        return false;
    }

    /**
     * Resolve panes which have been assigned to groups or roles down to user level
     *
     * When the currently logged-in user doesn't have the appropriate role or
     * is not part of appropriate group no resolution will be performed
     *
     * @return void
     */
    public function resolveRoleGroupMembers()
    {
        $conn = DashboardHome::getConn();
        $unionQuery = (new Select())
            ->columns('ds.name, ds.url, ds.id, dg.name AS `group`, NULL AS `role`')
            ->from(Dashlet::TABLE . ' ds')
            ->join(Dashboard::GROUP_ROLE_TABLE . ' grm', 'grm.dashboard_id = ds.id')
            ->join(DBUserManager::$dashboardGroup . ' dg', 'dg.id = grm.group_id')
            ->where(['ds.dashboard_id = ?' => $this->getPaneId()])
            ->groupBy(['ds.id', '`role`'])
            ->unionAll(
                (new Select())
                    ->columns('ds.name, ds.url, ds.id, NULL AS `group`, dr.role AS `role`')
                    ->from(Dashlet::TABLE . ' ds')
                    ->join(Dashboard::GROUP_ROLE_TABLE . ' grm', 'grm.dashboard_id = ds.id')
                    ->join(DBUserManager::$dashboardRole . ' dr', 'grm.role_id = dr.id')
                    ->where(['ds.dashboard_id = ?' => $this->getPaneId()])
                    ->groupBy(['ds.id', '`role`'])
            );

        $dashlets = $conn->select((new Select())
            ->columns('*')
            ->from(['dashlet' => $unionQuery]));

        foreach ($dashlets as $dashlet) {
            // Skip if this user isn't assigned to the role
            if ($dashlet->role && ! $this->getAuthUser()->hasRole($dashlet->role)) {
                continue;
            }

            // Skip if this user isn't member of the group
            if ($dashlet->group && ! $this->getAuthUser()->isMemberOf($dashlet->group)) {
                continue;
            }

            $dashlet = (new Dashlet($dashlet->name, $dashlet->url, $this))
                ->setDashletId($dashlet->id);
            $dashlet->loadMembers();

            if ($dashlet->hasMember($this->getAuthUser()->getUsername())) {
                continue;
            }

            $ownerDetail = DashboardManager::getWidgetOwnerDetail($dashlet);
            if (! $ownerDetail) {
                Logger::error(
                    'There is a corrupted dashlet in the database with the identity "%s" which doesn\'t'
                    . ' have an owner. This will be purged automatically!',
                    bin2hex($dashlet->getDashletId())
                );

                $conn->delete(Dashlet::TABLE, ['id = ?' => $dashlet->getDashletId()]);

                continue;
            }

            $conn->insert($dashlet->getTableMembership(), [
                'dashlet_id'    => $dashlet->getDashletId(),
                'user_id'       => $this->getAuthUser()->getIdentifier(),
                'type'          => $ownerDetail->type
            ]);
        }
    }

    /**
     * Return this pane's structure as array
     *
     * @return array
     */
    public function toArray()
    {
        $pane = [
            'id'        => $this->getPaneId(),
            'home_id'   => $this->getHome() ? $this->getHome()->getIdentifier() : null,
            'name'      => $this->getName(),
            'label'     => $this->getTitle(),
        ];

        if ($this->isDisabled() === true) {
            $pane['disabled'] = 1;
        }

        return $pane;
    }
}
