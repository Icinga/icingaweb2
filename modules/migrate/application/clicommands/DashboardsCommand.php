<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Exception\NotReadableError;
use Icinga\User;
use Icinga\Util\DirectoryIterator;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Pane;

class DashboardsCommand extends Command
{
    /**
     * Migrate local INI user dashboards to the database
     *
     * If you perform this operation over and over again, your database will be
     * flooded with the same entries. So, it is quite sufficient when you execute
     * this only once! or use the home option to migrate all the dashboards into this
     * new/existing home then you can manage them via the UI.
     *
     * USAGE
     *
     *  icingacli migrate dashboards [options]
     *
     * OPTIONS:
     *
     *  --home=<"home name">  Migrate local INI dashboards into this dashboard
     *                        home. (Default "Default Home")
     *
     *  --user=<username>     Migrate local INI dashboards only for the given
     *                        user. (Default *)
     *
     *  --delete              Remove all INI files after successfully migrated
     *                        the dashboards to the database.
     */
    public function indexAction()
    {
        $dashboardsPath = Config::resolvePath('dashboards');
        if (! is_dir($dashboardsPath)) {
            Logger::info('There are no local user dashboards to migrate');
            return;
        }

        $rc = 0;
        $deleteLegacyFiles = $this->params->get('delete');
        $user = $this->params->get('user');
        $home = $this->params->get('home');
        $dashboardDirs = new DirectoryIterator($dashboardsPath);

        $dashboard = new Dashboard();
        foreach ($dashboardDirs as $dashboardDir) {
            $username = $user;
            if ($username && $username !== $dashboardDirs->key()) {
                continue;
            }

            $username = $username ?: $dashboardDirs->key();
            $dashboardIni = $dashboardDir . DIRECTORY_SEPARATOR . 'dashboard.ini';
            $dashboardHome = $home ? new DashboardHome($home) : null;

            Logger::info('Migrating INI dashboards for user "%s" to database...', $username);

            try {
                $config = Config::fromIni($dashboardIni);
                if ($config->isEmpty()) {
                    continue;
                }

                $dashboard->setUser(new User($username));
                $dashboard->load();

                if ($dashboardHome) {
                    $dashboard->manageEntry($dashboardHome);
                    $dashboardHome->loadDashboardEntries();
                } else {
                    $dashboardHome = $dashboard->initGetDefaultHome();
                }

                foreach ($config as $key => $part) {
                    if (strpos($key, '.') === false) { // Panes
                        $counter = 1;
                        $pane = $key;
                        while ($dashboardHome->hasEntry($pane)) {
                            $pane = $key . $counter++;
                        }

                        $dashboardHome->createEntry($pane);
                        $dashboardHome->getEntry($pane)->setTitle($part->get('title', $pane));
                    } else { // Dashlets
                        list($pane, $dashlet) = explode('.', $key, 2);
                        if (! $dashboardHome->hasEntry($pane)) {
                            continue;
                        }

                        /** @var Pane $dashboardPane */
                        $dashboardPane = $dashboardHome->getEntry($pane);

                        $counter = 1;
                        $newDashelt = $dashlet;
                        while ($dashboardPane->hasEntry($newDashelt)) {
                            $newDashelt = $dashlet . $counter++;
                        }

                        $dashboardPane->createEntry($newDashelt, $part->get('url'));
                        $dashboardPane->getEntry($newDashelt)->setTitle($part->get('title', $newDashelt));
                    }
                }

                $panes = $dashboardHome->getEntries();
                $dashboardHome->setEntries([]);
                $dashboardHome->manageEntry($panes, null, true);

                if ($deleteLegacyFiles) {
                    unlink($dashboardIni);
                }
            } catch (NotReadableError $e) {
                if ($e->getPrevious() !== null) {
                    Logger::error('%s: %s', $e->getMessage(), $e->getPrevious()->getMessage());
                } else {
                    Logger::error($e->getMessage());
                }

                $rc = 128;
            }
        }

        if ($rc > 0) {
            Logger::error('Failed to migrate some user dashboards');
            exit($rc);
        }

        Logger::info('Successfully migrated all local user dashboards to the database');
    }
}
