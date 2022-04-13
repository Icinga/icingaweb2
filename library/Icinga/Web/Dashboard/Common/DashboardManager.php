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
    public static function getConn(): Connection
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
    public static function getSHA1(string $name): string
    {
        return sha1($name, true);
    }

    public function loadDashboardEntries(string $name = '')
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
    public function activateHome(DashboardHome $home): self
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
        } elseif (! $home->isDisabled()) {
            self::getConn()->update(DashboardHome::TABLE, ['disabled' => 1], [
                'id = ?' => $home->getUuid()
            ]);
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
                // Highest priority is 0, so count($entries) are always lowest prio + 1
                $priority = $home->getName() === DashboardHome::DEFAULT_HOME ? 0 : count($this->getEntries());
                $conn->insert(DashboardHome::TABLE, [
                    'name'     => $home->getName(),
                    'label'    => $home->getTitle(),
                    'username' => self::getUser()->getUsername(),
                    'priority' => $priority,
                    'type'     => $home->getType() !== Dashboard::SYSTEM ? $home->getType() : Dashboard::PRIVATE_DS
                ]);

                $home->setUuid($conn->lastInsertId());
            } else {
                $conn->update(DashboardHome::TABLE, [
                    'label'    => $home->getTitle(),
                    'priority' => $home->getPriority(),
                    'disabled' => 0
                ], ['id = ?' => $home->getUuid()]);
            }
        }

        return $this;
    }

    /**
     * Get and|or init the default dashboard home
     *
     * @return DashboardHome
     */
    public function initGetDefaultHome(): DashboardHome
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
    public function setUser(User $user): self
    {
        self::$user = $user;

        return $this;
    }

    /**
     * Get this dashboard's user
     *
     * @return User
     */
    public static function getUser(): User
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
    public static function getSystemDefaults(): array
    {
        return self::$defaultPanes;
    }

    /**
     * Browse all enabled modules configuration file and import all dashboards
     * provided by them into the DB table `icingaweb_module_dashlet`
     *
     * @return void
     */
    public static function deployModuleDashlets(): void
    {
        $mg = Icinga::app()->getModuleManager();
        foreach ($mg->getLoadedModules() as $module) {
            foreach ($module->getDashboard() as $dashboard) {
                $pane = new Pane($dashboard->getName());
                $pane->fromArray($dashboard->getProperties());

                $priority = 0;
                foreach ($dashboard->getDashlets() as $name => $configPart) {
                    $uuid = self::getSHA1($module->getName() . $pane->getName() . $name);
                    $dashlet = new Dashlet($name, $configPart['url'], $pane);
                    $dashlet->fromArray($configPart);
                    $dashlet
                        ->setUuid($uuid)
                        ->setModuleDashlet(true)
                        ->setPriority($priority++)
                        ->setModule($module->getName());

                    // As we don't have a setter for labels, this might be ignored by the data extractor
                    if (isset($configPart['label'])) {
                        $dashlet->setTitle($configPart['label']);
                    }

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
                    ->setPriority($priority++)
                    ->setModuleDashlet(true);

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
    public static function moduleDashletExist(Dashlet $dashlet): bool
    {
        $query = Model\ModuleDashlet::on(self::getConn())->filter(Filter::equal('id', $dashlet->getUuid()));
        $query->getSelectBase()->columns(new Expression('1'));

        return $query->execute()->hasResult();
    }

    /**
     * Insert or update the given module dashlet
     *
     * @param Dashlet $dashlet
     *
     * @return void
     */
    public static function updateOrInsertModuleDashlet(Dashlet $dashlet): void
    {
        if (! $dashlet->isModuleDashlet()) {
            return;
        }

        if (! self::moduleDashletExist($dashlet)) {
            self::getConn()->insert('icingaweb_module_dashlet', [
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
            self::getConn()->update('icingaweb_module_dashlet', [
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
    public static function getModuleDashlets(): array
    {
        $dashlets = [];
        $query = Model\ModuleDashlet::on(self::getConn());
        foreach ($query as $moduleDashlet) {
            $dashlet = new Dashlet($moduleDashlet->name, $moduleDashlet->url);
            if ($moduleDashlet->description) {
                $dashlet->setDescription(t($moduleDashlet->description));
            }

            $dashlet->fromArray([
                'label'    => t($moduleDashlet->label),
                'priority' => $moduleDashlet->priority,
                'uuid'     => $moduleDashlet->id,
                'module'   => $moduleDashlet->module
            ]);

            if (($pane = $moduleDashlet->pane)) {
                $dashlet->setPane(new Pane($pane));
            }

            $dashlet->setModuleDashlet(true);
            $dashlets[$dashlet->getModule()][$dashlet->getName()] = $dashlet;
        }

        return $dashlets;
    }
}
