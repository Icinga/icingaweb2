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
use Icinga\Web\Dashboard\Dashlet;
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
     *
     *  --silent              Suppress all kind of DB errors and have them handled automatically.
     */
    public function indexAction()
    {
        $dashboardsPath = Config::resolvePath('dashboards');
        if (! is_dir($dashboardsPath)) {
            Logger::info('There are no local user dashboards to migrate');
            return;
        }

        $silent = $this->params->get('silent');
        $user = $this->params->get('user');
        $home = $this->params->get('home');
        $deleteLegacyFiles = $this->params->get('delete');

        $dashboardDirs = new DirectoryIterator($dashboardsPath);
        $dashboardHome = ! $home ? null : new DashboardHome($home);
        $dashboard = new Dashboard();

        $rc = 0;
        foreach ($dashboardDirs as $dashboardDir) {
            $username = $user;
            if ($username && $username !== $dashboardDirs->key()) {
                continue;
            }

            $username = $username ?: $dashboardDirs->key();
            $dashboardIni = $dashboardDir . '/dashboard.ini';

            Logger::info('Migrating INI dashboards for user "%s" to database...', $username);

            try {
                $config = Config::fromIni($dashboardIni);
                if ($config->isEmpty()) {
                    continue;
                }

                $dashboard->setUser(new User($username));
                $dashboard->load();

                if ($dashboardHome) {
                    if ($dashboard->hasEntry($dashboardHome->getName())) {
                        $dashboardHome = $dashboard->getEntry($dashboardHome->getName());
                    } else {
                        $dashboard->manageEntry($dashboardHome);
                    }
                } else {
                    $dashboardHome = $dashboard->initGetDefaultHome();
                }

                $dashboardHome->loadDashboardEntries();

                $panes = [];
                $parsedPanes = [];
                $parsedDashlets = [];
                foreach ($config as $key => $part) {
                    if (strpos($key, '.') === false) { // Panes
                        $pane = $key;
                        if ($silent && $dashboardHome->hasEntry($pane)) {
                            while ($dashboardHome->hasEntry($pane)) {
                                $pane = $this->getUniqueName($pane);
                            }
                        } elseif ($dashboardHome->hasEntry($pane)) {
                            do {
                                $pane = readline(sprintf(
                                    'Dashboard Pane "%s" already exists within the "%s" Dashboard Home.' . "\n" .
                                    'Please enter another name for this pane or rerun the command with the "silent"' .
                                    ' param to suppress such errors!: ',
                                    $pane,
                                    $dashboardHome->getTitle()
                                ));
                            } while (empty($pane) || $dashboardHome->hasEntry($pane));
                        }

                        $parsedPanes[$key] = $pane;
                        $panes[$pane] = (new Pane($pane))
                            ->setHome($dashboardHome)
                            ->setTitle($part->get('title', $pane));
                    } else { // Dashlets
                        list($pane, $dashletName) = explode('.', $key, 2);
                        if (! isset($parsedDashlets[$pane])) {
                            $parsedDashlets[$pane] = [];
                        }

                        $dashlet = new Dashlet($dashletName, $part->get('url'));
                        $dashlet->setTitle($part->get('title', $dashletName));
                        $parsedDashlets[$pane][$dashlet->getName()] = $dashlet;
                    }
                }

                $dashboardHome->manageEntry($panes);

                foreach ($parsedDashlets as $key => $dashlets) {
                    if (! isset($parsedPanes[$key])) {
                        continue;
                    }

                    /** @var Pane $pane */
                    $pane = $panes[$parsedPanes[$key]];
                    $pane->loadDashboardEntries();

                    /** @var  Dashlet $dashlet */
                    foreach ($dashlets as $name => $dashlet) {
                        if ($silent && $pane->hasEntry($name)) {
                            while ($pane->hasEntry($name)) {
                                $name = $this->getUniqueName($name);
                            }
                        } elseif ($pane->hasEntry($name)) {
                            do {
                                $name = readline(sprintf(
                                    'Dashlet "%s" already exists within the "%s" Dashboard Pane.' . "\n" .
                                    'Please enter another name for this Dashlet or rerun the command with the' .
                                    ' "silent" param to suppress such errors!: ',
                                    $name,
                                    $pane->getTitle()
                                ));
                            } while (empty($name) || $pane->hasEntry($name));
                        }

                        $dashlet->setName($name);
                    }

                    $pane->manageEntry($dashlets);
                }

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
            } catch (\PDOException $e) {
                Logger::error($e->getMessage());

                $rc = 128;
                break;
            }
        }

        if ($rc > 0) {
            Logger::error('Failed to migrate some user dashboards');
            exit($rc);
        }

        Logger::info('Successfully migrated all local user dashboards to the database');
    }

    /**
     * Get a more unique version of the given name
     *
     * @param string $name
     *
     * @return string
     */
    protected function getUniqueName(string $name): string
    {
        if (preg_match('/(\d+)$/', $name, $matches)) {
            $name = preg_replace('/\d+$/', ++$matches[1], $name);
        } else {
            $name .= 1;
        }

        return $name;
    }
}
