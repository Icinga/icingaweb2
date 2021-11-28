<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Logger;
use Icinga\Common\DashboardManager;
use Icinga\Common\DBUserManager;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Widget\Dashboard;
use ipl\Sql\Expression;
use ipl\Sql\Select;

/**
 * The entrypoint for dashboard homes
 */
class HomeMenu extends Menu
{
    use DBUserManager;

    public function __construct()
    {
        parent::__construct();

        $this->resolveRoleGroupMembers();
        $this->initHomes();
    }

    /**
     * Resolve dashboard homes which have been assigned to groups or roles down to user level
     *
     * When the currently logged-in user doesn't have the appropriate role or
     * isn't part of appropriate group no resolution will be performed
     *
     * @return void
     */
    protected function resolveRoleGroupMembers()
    {
        $conn = DashboardHome::getConn();
        $unionQuery = (new Select())
            ->columns('dh.name, dh.id, dg.name AS `group`, NULL AS `role`')
            ->from(DashboardHome::TABLE . ' dh')
            ->join(Dashboard::GROUP_ROLE_TABLE . ' grm', 'grm.home_id = dh.id')
            ->join(DBUserManager::$dashboardGroup . ' dg', 'grm.group_id = dg.id')
            ->groupBy(['dh.id', '`role`'])
            ->unionAll(
                (new Select())
                    ->columns('dh.name, dh.id, NULL AS `group`, dr.role AS `role`')
                    ->from(DashboardHome::TABLE . ' dh')
                    ->join(Dashboard::GROUP_ROLE_TABLE . ' grm', 'grm.home_id = dh.id')
                    ->join(DBUserManager::$dashboardRole . ' dr', 'grm.role_id = dr.id')
                    ->groupBy(['dh.id', '`role`'])
            );

        $homes = $conn->select((new Select())
            ->columns('*')
            ->from(['home' => $unionQuery]));

        foreach ($homes as $home) {
            // Skip if this user isn't assigned to the role
            if ($home->role && ! $this->getAuthUser()->hasRole($home->role)) {
                continue;
            }

            // Skip if this user isn't member of the group
            if ($home->group && ! $this->getAuthUser()->isMemberOf($home->group)) {
                continue;
            }

            $homeWidget = new DashboardHome($home->name, ['identifier' => $home->id]);
            if ($homeWidget->hasMember($this->getAuthUser()->getUsername())) {
                continue;
            }

            $ownerDetail = DashboardManager::getWidgetOwnerDetail($homeWidget);
            if (! $ownerDetail) {
                Logger::error(
                    'There is a corrupted dashboard home in the database with the identity "%s" which doesn\'t'
                    . ' have an owner. This will be purged automatically!',
                    $homeWidget->getName()
                );

                $conn->delete(DashboardHome::TABLE, ['id = ?' => $homeWidget->getIdentifier()]);

                continue;
            }

            $conn->insert($homeWidget->getTableMembership(), [
                'home_id'   => $homeWidget->getIdentifier(),
                'user_id'   => $this->getAuthUser()->getIdentifier(),
                'type'      => $ownerDetail->type,
                'disabled'  => $ownerDetail->disabled
            ]);
        }
    }

    /**
     * Set up the dashboard home navigation items
     *
     * Loads currently logged-in user specific dashboards, shared and public homes from the DB and
     * some system homes from the navigation and places as child items to the dashboard main menu
     *
     * @return void
     */
    public function initHomes()
    {
        $menuItem = $this->getItem('dashboard');
        $conn = DashboardHome::getConn();
        $homes = $conn->select((new Select())
            ->columns('dh.*, hum.disabled, hum.type, hum.owner, COALESCE(dho.priority, 0) AS `order`')
            ->from(DashboardHome::TABLE . ' dh')
            ->join('home_member hum', 'hum.home_id = dh.id')
            ->join('dashboard_user du', ['hum.user_id = du.id', 'du.name = ?' => $this->getAuthUser()->getUsername()])
            ->joinLeft('dashboard_home_order dho', ['dho.home_id = dh.id', 'dho.user_id = du.id'])
            ->orderBy('`order`', 'DESC'));

        foreach ($homes as $home) {
            $dashboardHome = new DashboardHome($home->name, [
                'label'      => $home->label,
                'priority'   => $home->order,
                'user'       => $this->getAuthUser(),
                'identifier' => $home->id,
                'disabled'   => $home->disabled,
                'type'       => in_array($home->name, DashboardHome::DEFAULT_HOME_ENUMS)
                    ? Dashboard::SYSTEM
                    : $home->type,
            ]);

            if ($home->owner === 'y') {
                $dashboardHome->setOwner($this->getAuthUser()->getUsername());
            } elseif (in_array($dashboardHome->getType(), [Dashboard::SHARED, Dashboard::PUBLIC_DS])) {
                if (($owner = DashboardManager::getWidgetOwnerDetail($dashboardHome))) {
                    $dashboardHome->setOwner($owner->name);
                }
            }

            $menuItem->addChild($dashboardHome);
        }

        $maxId = $conn->select((new Select())->columns('MAX(id) AS maxId')->from(DashboardHome::TABLE))->fetch();
        $self = clone $this;
        $self->items = [];
        $self->load('dashboard-home');

        foreach ($self as $home) {
            if (! $home instanceof DashboardHome) {
                continue;
            }

            /** @var DashboardHome $homeItem */
            if (($homeItem = $menuItem->getChildren()->findItem($home->getName()))) {
                if ($homeItem->panePersists() || $homeItem->isDisabled()) {
                    $homeItem->setPanes($home->getChildren());

                    continue;
                }

                // This home has been edited by the user, e.g by disabling the entire
                // home, but now it has been re-enabled and can be removed from the DB
                $conn->delete(DashboardHome::TABLE, ['id = ?' => $homeItem->getIdentifier()]);
            }

            $home
                ->setAuthUser($this->getAuthUser())
                ->setPanes($home->getChildren())
                ->setChildren([]) // We don't need them anymore
                ->setType(Dashboard::SYSTEM)
                ->setIdentifier(++$maxId->maxId);

            $menuItem->addChild($home);
        }
    }
}
