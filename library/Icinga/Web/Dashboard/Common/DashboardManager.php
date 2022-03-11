<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Common\Database;
use Icinga\Exception\ProgrammingError;
use Icinga\Model;
use Icinga\User;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\HomeMenu;
use Icinga\Web\Navigation\DashboardHome;
use ipl\Orm\Query;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;
use ipl\Web\Url;

trait DashboardManager
{
    use Database;

    /** @var User */
    private static $user;
    
    /** @var Connection */
    private static $conn;

    /**
     * A list of @see DashboardHome
     *
     * @var DashboardHome[]
     */
    private $homes = [];

    /**
     * A list of default panes loaded from monitoring|icingadb module
     *
     * @var Pane[]
     */
    private $defaultPanes = [];

    public function load()
    {
        $this->loadHomesFromMenu();
        $this->loadDashboards();

        $this->initAndGetDefaultHome();
        $this->deployModuleDashlets();
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
            self::$conn = (new self())->getDb();
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
     * Load dashboard homes from the navigation menu
     *
     * @return $this
     */
    public function loadHomesFromMenu()
    {
        $menu = new HomeMenu();
        foreach ($menu->getItem('dashboard')->getChildren() as $home) {
            if (! $home instanceof DashboardHome) {
                continue;
            }

            $this->homes[$home->getName()] = $home;
        }

        return $this;
    }

    /**
     * Load dashboards assigned to the given home or active home being loaded
     *
     * @param string $name
     *
     * @return $this
     */
    public function loadDashboards($name = null)
    {
        if ($name && $this->hasHome($name)) {
            $home = $this->getHome($name);
        } else {
            $requestRoute = Url::fromRequest();
            if ($requestRoute->getPath() === Dashboard::BASE_ROUTE) {
                $home = $this->initAndGetDefaultHome();
            } else {
                $homeParam = $requestRoute->getParam('home');
                if (empty($homeParam) || ! $this->hasHome($homeParam)) {
                    if (! ($home = $this->rewindHomes())) {
                        return $this;
                    }
                } else {
                    $home = $this->getHome($homeParam);
                }
            }
        }

        $this->activateHome($home);
        $home->loadDashboardsFromDB();

        return $this;
    }

    /**
     * Get a dashboard home by the given name
     *
     * @param string $name
     *
     * @return DashboardHome
     */
    public function getHome($name)
    {
        if ($this->hasHome($name)) {
            return $this->homes[$name];
        }

        throw new ProgrammingError('Trying to retrieve invalid dashboard home "%s"', $name);
    }

    /**
     * Get all dashboard homes assigned to the active user
     *
     * @return DashboardHome[]
     */
    public function getHomes()
    {
        return $this->homes;
    }

    /**
     * Set this home's dashboards
     *
     * @param DashboardHome|DashboardHome[] $homes
     */
    public function setHomes($homes)
    {
        if ($homes instanceof DashboardHome) {
            $homes = [$homes->getName() => $homes];
        }

        $this->homes = $homes;

        return $this;
    }

    /**
     * Get whether the given home exist
     *
     * @param string  $name
     *
     * @return bool
     */
    public function hasHome($name)
    {
        return array_key_exists($name, $this->homes);
    }

    /**
     * Add a new pane to this home
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

        $home->setActive(true);

        return $this;
    }

    /**
     * Checks if this home has any panes
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
     * Reset the current position of the internal dashboard homes pointer
     *
     * @return false|DashboardHome
     */
    public function rewindHomes()
    {
        return reset($this->homes);
    }

    /**
     * Remove a specific pane form this home
     *
     * @param DashboardHome|string $home
     *
     * @return $this
     */
    public function removeHome($home)
    {
        $name = $home instanceof DashboardHome ? $home->getName() : $home;
        if (! $this->hasHome($name)) {
            throw new ProgrammingError('Trying to remove invalid dashboard home "%s"', $name);
        }

        $home = $home instanceof DashboardHome ? $home : $this->getHome($home);
        if (! $home->isDisabled()) {
            $home->removePanes();

            self::getConn()->delete(DashboardHome::TABLE, ['id = ?' => $home->getUuid()]);
        }

        return $this;
    }

    /**
     * Remove all panes from this home, unless you specified the panes
     *
     * @param DashboardHome[] $homes
     *
     * @return $this
     */
    public function removeHomes(array $homes = [])
    {
        $homes = ! empty($homes) ? $homes : $this->getHomes();
        foreach ($homes as $home) {
            $this->removeHome($home);
        }

        return $this;
    }

    /**
     * Manage the given pane(s)
     *
     * @param DashboardHome $home
     *
     * @return $this
     */
    public function manageHome(DashboardHome $home)
    {
        $conn = self::getConn();

        if (! $this->hasHome($home->getName())) {
            $conn->insert(DashboardHome::TABLE, [
                'name'      => $home->getName(),
                'label'     => $home->getLabel(),
                'username'  => self::getUser()->getUsername(),
                'priority'  => $home->getPriority(),
                'type'      => $home->getType() !== Dashboard::SYSTEM ? $home->getType() : Dashboard::PRIVATE_DS
            ]);

            $home->setUuid($conn->lastInsertId());
        } elseif ($home->getName() !== DashboardHome::DEFAULT_HOME) {
            $conn->update(DashboardHome::TABLE, ['label' => $home->getLabel()], ['id = ?' => $home->getUuid()]);
        }

        return $this;
    }

    /**
     * Get an array with home name=>title format
     *
     * @return array
     */
    public function getHomeKeyTitleArr()
    {
        $panes = [];
        foreach ($this->getHomes() as $home) {
            if ($home->isDisabled()) {
                continue;
            }

            $panes[$home->getName()] = $home->getLabel();
        }

        return $panes;
    }

    /**
     * Get and/or init the default dashboard home
     *
     * @return DashboardHome
     */
    public function initAndGetDefaultHome()
    {
        if ($this->hasHome(DashboardHome::DEFAULT_HOME)) {
            return $this->getHome(DashboardHome::DEFAULT_HOME);
        }

        $default = new DashboardHome(DashboardHome::DEFAULT_HOME);
        $this->manageHome($default);

        $this->homes[$default->getName()] = $default;

        return $default;
    }

    /**
     * Set this dashboard's user
     *
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        self::$user = $user;

        return $this;
    }

    /**
     * Get this dashboard's user
     *
     * @return User
     */
    public static function getUser()
    {
        if (self::$user === null) {
            self::$user = Auth::getInstance()->getUser();
        }

        return self::$user;
    }

    /**
     * Get system defaults which are normally being
     * provided by icingadb or monitoring module
     *
     * @return Pane[]
     */
    public function getSystemDefaults()
    {
        return $this->defaultPanes;
    }

    /**
     * Browse all enabled modules configuration file and import all dashboards
     * provided by them into the DB table `module_dashlet`
     *
     * @return void
     */
    public function deployModuleDashlets()
    {
        $moduleManager = Icinga::app()->getModuleManager();
        foreach ($moduleManager->getLoadedModules() as $module) {
            foreach ($module->getDashboard() as $dashboardPane) {
                foreach ($dashboardPane->getDashlets() as $dashlet) {
                    $identifier = self::getSHA1(
                        $module->getName() . $dashboardPane->getName() . $dashlet->getName()
                    );
                    $dashlet->setUuid($identifier);
                    self::updateOrInsertModuleDashlet($dashlet, $module->getName());
                }

                if (in_array($module->getName(), ['monitoring', 'icingadb'], true)) {
                    $this->defaultPanes[$dashboardPane->getName()] = $dashboardPane;
                }
            }

            $priority = 0;
            foreach ($module->getDashlet() as $dashlet) {
                $identifier = self::getSHA1($module->getName() . $dashlet->getName());

                $dashlet->setUuid($identifier);
                $dashlet->setPriority($priority++);

                self::updateOrInsertModuleDashlet($dashlet, $module->getName());
            }
        }
    }

    /**
     * Get whether the given module Dashlet already exists
     *
     * @param Dashlet $dashlet
     *
     * @return bool
     */
    public static function moduleDashletExist(Dashlet $dashlet)
    {
        $query = Model\ModuleDashlet::on(self::getConn())->filter(Filter::equal('id', $dashlet->getUuid()));
        $query->getSelectBase()->columns(new Expression('1'));

        return $query->execute()->hasResult();
    }

    /**
     * Insert or update the given module dashlet
     *
     * @param Dashlet $dashlet
     * @param string  $module
     *
     * @return void
     */
    public static function updateOrInsertModuleDashlet(Dashlet $dashlet, $module)
    {
        if (! self::moduleDashletExist($dashlet)) {
            self::getConn()->insert('module_dashlet', [
                'id'            => $dashlet->getUuid(),
                'name'          => $dashlet->getName(),
                'label'         => $dashlet->getTitle(),
                'pane'          => $dashlet->getPane() ? $dashlet->getPane()->getName() : null,
                'module'        => $module,
                'url'           => $dashlet->getUrl()->getRelativeUrl(),
                'description'   => $dashlet->getDescription(),
                'priority'      => $dashlet->getPriority()
            ]);
        } else {
            self::getConn()->update('module_dashlet', [
                'label'         => $dashlet->getTitle(),
                'url'           => $dashlet->getUrl()->getRelativeUrl(),
                'description'   => $dashlet->getDescription(),
                'priority'      => $dashlet->getPriority()
            ], ['id = ?' => $dashlet->getUuid()]);
        }
    }

    /**
     * Get module dashlets from the database
     *
     * @return array
     */
    public static function getModuleDashlets(Query $query)
    {
        $dashlets = [];
        foreach ($query as $moduleDashlet) {
            $dashlet = new Dashlet($moduleDashlet->name, $moduleDashlet->url);
            if ($moduleDashlet->description) {
                $dashlet->setDescription(t($moduleDashlet->description));
            }

            $dashlet->fromArray([
                'label'         => t($moduleDashlet->label),
                'priority'      => $moduleDashlet->priority,
                'uuid'          => $moduleDashlet->id
            ]);

            if (($pane = $moduleDashlet->pane)) {
                $dashlet->setPane(new Pane($pane));
            }

            $dashlets[$moduleDashlet->module][$dashlet->getName()] =  $dashlet;
        }

        return $dashlets;
    }
}
