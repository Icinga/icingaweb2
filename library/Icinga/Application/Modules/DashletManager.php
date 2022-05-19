<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Modules;

use Icinga\Application\Icinga;
use Icinga\Model;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Util\DBUtils;
use ipl\Sql\Expression;
use ipl\Stdlib\Filter;

class DashletManager
{
    /**
     * A list of default panes loaded from monitoring|icingadb module
     *
     * @var Pane[]
     */
    private static $defaultPanes = [];

    /**
     * Get system defaults which are normally being provided by icingadb or monitoring module
     *
     * @return Pane[]
     */
    public static function getSystemDefaults(): array
    {
        return self::$defaultPanes;
    }

    /**
     * Get whether the given module Dashlet already exists
     *
     * @param Dashlet $dashlet
     *
     * @return bool
     */
    public static function dashletExist(Dashlet $dashlet): bool
    {
        $query = Model\ModuleDashlet::on(DBUtils::getConn())
            ->disableDefaultSort()
            ->setColumns([new Expression('1')])
            ->filter(Filter::equal('id', $dashlet->getUuid()));

        return $query->execute()->hasResult();
    }

    /**
     * Browse all enabled modules configuration file and import all dashboards
     * provided by them into the DB table `icingaweb_module_dashlet`
     *
     * @return void
     */
    public static function deployDashlets(): void
    {
        $user = Dashboard::getUser();
        $mm = Icinga::app()->getModuleManager();
        foreach ($mm->getLoadedModules() as $module) {
            foreach ($module->getDashboard() as $dashboard) {
                $pane = new Pane($dashboard->getName());
                $pane->setProperties($dashboard->getProperties());

                $priority = 0;
                foreach ($dashboard->getDashlets() as $name => $configPart) {
                    $uuid = Dashboard::getSHA1($module->getName() . $pane->getName() . $name);
                    $dashlet = new Dashlet($name, $configPart['url'], $pane);
                    $dashlet->setProperties($configPart);
                    $dashlet
                        ->setUuid($uuid)
                        ->setModuleDashlet(true)
                        ->setPriority($priority++)
                        ->setModule($module->getName());

                    // As we don't have a setter for labels, this might be ignored by the data extractor
                    if (isset($configPart['label'])) {
                        $dashlet->setTitle($configPart['label']);
                    }

                    self::updateOrInsertDashlet($dashlet);
                    $pane->addEntry($dashlet);
                }

                if (in_array($module->getName(), ['monitoring', 'icingadb'], true)) {
                    if ($user->can($mm::MODULE_PERMISSION_NS . $module->getName())) {
                        self::$defaultPanes[$pane->getName()] = $pane;
                    }
                }
            }

            $priority = 0;
            foreach ($module->getDashlets() as $dashlet) {
                $identifier = Dashboard::getSHA1($module->getName() . $dashlet->getName());
                $newDashlet = new Dashlet($dashlet->getName(), $dashlet->getUrl());
                $newDashlet->setProperties($dashlet->getProperties());
                $newDashlet
                    ->setUuid($identifier)
                    ->setModule($module->getName())
                    ->setPriority($priority++)
                    ->setModuleDashlet(true);

                self::updateOrInsertDashlet($newDashlet);
            }
        }
    }

    /**
     * Insert or update the given module dashlet
     *
     * @param Dashlet $dashlet
     *
     * @return void
     */
    public static function updateOrInsertDashlet(Dashlet $dashlet): void
    {
        if (! $dashlet->isModuleDashlet()) {
            return;
        }

        $conn = DBUtils::getConn();
        if (! self::dashletExist($dashlet)) {
            $conn->insert('icingaweb_module_dashlet', [
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
            $conn->update('icingaweb_module_dashlet', [
                'label'       => $dashlet->getTitle(),
                'url'         => $dashlet->getUrl()->getRelativeUrl(),
                'description' => $dashlet->getDescription(),
                'priority'    => $dashlet->getPriority(),
            ], ['id = ?' => $dashlet->getUuid()]);

            self::ensureItIsNotOrphaned($dashlet);
        }
    }

    /**
     * Get module dashlets from the database
     *
     * @return array
     */
    public static function getDashlets(): array
    {
        $dashlets = [];
        $query = Model\ModuleDashlet::on(DBUtils::getConn());
        foreach ($query as $moduleDashlet) {
            $dashlet = new Dashlet($moduleDashlet->name, $moduleDashlet->url);
            $dashlet
                ->setUuid($moduleDashlet->id)
                ->setTitle($moduleDashlet->label)
                ->setModuleDashlet(true)
                ->setModule($moduleDashlet->module)
                ->setPriority($moduleDashlet->priority);

            if (! self::ensureItIsNotOrphaned($dashlet)) {
                continue;
            }

            if ($moduleDashlet->description) {
                $dashlet->setDescription(t($moduleDashlet->description));
            }

            if (($pane = $moduleDashlet->pane)) {
                $dashlet->setPane(new Pane($pane));
            }

            $dashlets[$dashlet->getModule()][$dashlet->getName()] = $dashlet;
        }

        return $dashlets;
    }

    /**
     * Remove the given module dashlet from the database
     *
     * @param Dashlet $dashlet
     *
     * @return void
     */
    public static function ensureItIsNotOrphaned(Dashlet $dashlet): bool
    {
        if (self::isOrphaned($dashlet)) {
            // Module doesn't exist anymore
            DBUtils::getConn()->delete('icingaweb_module_dashlet', ['id = ?' => $dashlet->getUuid()]);
        }

        return ! self::isOrphaned($dashlet) && self::isUsable($dashlet);
    }

    /**
     * Get whether the current user has the required permissions to access the given module dashlet
     * and whether the module from which the given dashlet originates is still enabled
     *
     * @param Dashlet $dashlet
     *
     * @return bool
     */
    public static function isUsable(Dashlet $dashlet): bool
    {
        if (! $dashlet->isModuleDashlet()) {
            return true;
        }

        $mm = Icinga::app()->getModuleManager();
        return $mm->hasEnabled($dashlet->getModule())
            && Dashboard::getUser()->can(Manager::MODULE_PERMISSION_NS . $dashlet->getModule());
    }

    /**
     * Get whether the module from which the given dashlet originates is installed on the system
     *
     * @param Dashlet $dashlet
     *
     * @return bool
     */
    public static function isOrphaned(Dashlet $dashlet): bool
    {
        if (! $dashlet->isModuleDashlet()) {
            return false;
        }

        return ! Icinga::app()->getModuleManager()->hasInstalled($dashlet->getModule());
    }
}
