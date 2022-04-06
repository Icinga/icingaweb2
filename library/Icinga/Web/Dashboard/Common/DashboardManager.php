<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Application\Icinga;
use Icinga\Application\Modules\DashletContainer;
use Icinga\Authentication\Auth;
use Icinga\Common\Database;
use Icinga\Exception\ProgrammingError;
use Icinga\Model;
use Icinga\User;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Menu;
use Icinga\Web\Navigation\DashboardPane;
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
     * A list of default panes loaded from monitoring|icingadb module
     *
     * @var Pane[]
     */
    private static $defaultPanes = [];

    public function load()
    {
        $this->setEntries((new Menu())->loadHomes());
        $this->loadDashboardEntries();

        $this->initGetDefaultHome();
        self::deployModuleDashlets();
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

    public function loadDashboardEntries($name = '')
    {
        if ($name && $this->hasEntry($name)) {
            $home = $this->getEntry($name);
        } else {
            $requestRoute = Url::fromRequest();
            if ($requestRoute->getPath() === Dashboard::BASE_ROUTE) {
                $home = $this->initGetDefaultHome();
            } else {
                $homeParam = $requestRoute->getParam('home');
                if (empty($homeParam) || ! $this->hasEntry($homeParam)) {
                    if (! ($home = $this->rewindEntries())) {
                        // No dashboard homes
                        return $this;
                    }
                } else {
                    $home = $this->getEntry($homeParam);
                }
            }
        }

        $this->activateHome($home);
        $home->loadDashboardEntries();

        return $this;
    }

    /**
     * Activates the given home and deactivates all other active homes
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
     * Get the active home currently being loaded
     *
     * @return ?DashboardHome
     */
    public function getActiveHome()
    {
        /** @var DashboardHome $home */
        foreach ($this->getEntries() as $home) {
            if ($home->getActive()) {
                return $home;
            }
        }

        return null;
    }

    public function removeEntry($home)
    {
        $name = $home instanceof DashboardHome ? $home->getName() : $home;
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to remove invalid dashboard home "%s"', $name);
        }

        $home = $home instanceof DashboardHome ? $home : $this->getEntry($home);
        $home->removeEntries();
        if ($home->getName() !== DashboardHome::DEFAULT_HOME) {
            self::getConn()->delete(DashboardHome::TABLE, ['id = ?' => $home->getUuid()]);
        }

        return $this;
    }

    public function manageEntry($entry, BaseDashboard $origin = null, $manageRecursive = false)
    {
        $conn = self::getConn();
        $homes = is_array($entry) ? $entry : [$entry];

        /** @var DashboardHome $home */
        foreach ($homes as $home) {
            if (! $this->hasEntry($home->getName())) {
                $priority = $home->getName() === DashboardHome::DEFAULT_HOME ? 0 : count($this->getEntries());
                $conn->insert(DashboardHome::TABLE, [
                    'name'     => $home->getName(),
                    'label'    => $home->getTitle(),
                    'username' => self::getUser()->getUsername(),
                    // highest priority is 0, so count($entries) are always lowest prio + 1
                    'priority' => $priority,
                    'type'     => $home->getType() !== Dashboard::SYSTEM ? $home->getType() : Dashboard::PRIVATE_DS
                ]);

                $home->setUuid($conn->lastInsertId());
            } else {
                $conn->update(DashboardHome::TABLE, [
                    'label'    => $home->getTitle(),
                    'priority' => $home->getPriority()
                ], ['id = ?' => $home->getUuid()]);
            }
        }

        return $this;
    }

    /**
     * Get and|or init the default dashboard home
     *
     * @return BaseDashboard
     */
    public function initGetDefaultHome()
    {
        if ($this->hasEntry(DashboardHome::DEFAULT_HOME)) {
            return $this->getEntry(DashboardHome::DEFAULT_HOME);
        }

        $default = new DashboardHome(DashboardHome::DEFAULT_HOME);
        $this->manageEntry($default);
        $this->addEntry($default);

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
    public static function getSystemDefaults()
    {
        return self::$defaultPanes;
    }

    /**
     * Browse all enabled modules configuration file and import all dashboards
     * provided by them into the DB table `module_dashlet`
     *
     * @return void
     */
    public static function deployModuleDashlets()
    {
        $moduleManager = Icinga::app()->getModuleManager();
        foreach ($moduleManager->getLoadedModules() as $module) {
            /** @var DashboardPane $dashboardPane */
            foreach ($module->getDashboard() as $dashboardPane) {
                $pane = new Pane($dashboardPane->getName());
                $pane->setTitle($dashboardPane->getLabel());
                $pane->fromArray($dashboardPane->getAttributes());

                foreach ($dashboardPane->getIterator()->getItems() as $dashletItem) {
                    $uuid = self::getSHA1($module->getName() . $pane->getName() . $dashletItem->getName());
                    $dashlet = new Dashlet($dashletItem->getName(), $dashletItem->getUrl(), $pane);
                    $dashlet->fromArray($dashletItem->getAttributes());
                    $dashlet
                        ->setUuid($uuid)
                        ->setModule($module->getName())
                        ->setModuleDashlet(true)
                        ->setPriority($dashletItem->getPriority());

                    self::updateOrInsertModuleDashlet($dashlet);
                    $pane->addEntry($dashlet);
                }

                if (in_array($module->getName(), ['monitoring', 'icingadb'], true)) {
                    self::$defaultPanes[$pane->getName()] = $pane;
                }
            }

            $priority = 0;
            foreach ($module->getDashlets() as $dashlet) {
                $identifier = self::getSHA1($module->getName() . $dashlet->getName());
                $newDashlet = new Dashlet($dashlet->getName(), $dashlet->getUrl());
                $newDashlet->fromArray($dashlet->getProperties());
                $newDashlet
                    ->setUuid($identifier)
                    ->setModule($module->getName())
                    ->setModuleDashlet(true);

                if (! $newDashlet->getPriority()) {
                    $newDashlet->setPriority($priority);
                }

                self::updateOrInsertModuleDashlet($newDashlet);
                $priority++;
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
     * @param string $module
     *
     * @return void
     */
    public static function updateOrInsertModuleDashlet(Dashlet $dashlet)
    {
        if (! $dashlet->isModuleDashlet()) {
            return;
        }

        if (! self::moduleDashletExist($dashlet)) {
            self::getConn()->insert('module_dashlet', [
                'id'          => $dashlet->getUuid(),
                'name'        => $dashlet->getName(),
                'label'       => $dashlet->getTitle(),
                'pane'        => $dashlet->getPane() ? $dashlet->getPane()->getName() : null,
                'module'      => $dashlet->getModule(),
                'url'         => $dashlet->getUrl()->getRelativeUrl(),
                'description' => $dashlet->getDescription(),
                'priority'    => $dashlet->getPriority()
            ]);
        } else {
            self::getConn()->update('module_dashlet', [
                'label'       => $dashlet->getTitle(),
                'url'         => $dashlet->getUrl()->getRelativeUrl(),
                'description' => $dashlet->getDescription(),
                'priority'    => $dashlet->getPriority()
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
                'label'    => t($moduleDashlet->label),
                'priority' => $moduleDashlet->priority,
                'uuid'     => $moduleDashlet->id
            ]);

            if (($pane = $moduleDashlet->pane)) {
                $dashlet->setPane(new Pane($pane));
            }

            $dashlets[$moduleDashlet->module][$dashlet->getName()] = $dashlet;
        }

        return $dashlets;
    }
}
