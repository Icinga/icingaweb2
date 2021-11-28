<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Exception\NotReadableError;
use Icinga\Util\DirectoryIterator;
use Icinga\Common\DashboardManager;

class DashboardsCommand extends Command
{
    use DashboardManager;

    /**
     * Migrate local INI user dashboards to a database
     *
     * If you perform this operation over and over again, your database will be flooded
     * with the same entries. So, it is quite sufficient when you execute this only once!
     *
     * USAGE
     *
     *  icingacli migrate dashboards [options]
     *
     * OPTIONS:
     *
     *  --user=<username>   Migrate local INI dashboards only for
     *                      the given user. (Default *)
     *
     *  --delete            Remove all INI files after successfully
     *                      migrated the dashboards to DB.
     */
    public function indexAction()
    {
        $dashboardsPath = Config::resolvePath('dashboards');
        if (! file_exists($dashboardsPath)) {
            Logger::info('There are no local user dashboards to migrate');
            return;
        }

        $rc = 0;
        $user = $this->params->get('user');
        $deleteLegacyFiles = $this->params->get('delete');
        $dashboardDirs = new DirectoryIterator($dashboardsPath);

        foreach ($dashboardDirs as $dashboardDir) {
            $username = $user;
            if ($username && $username !== $dashboardDirs->key()) {
                continue;
            }

            if (! $username) {
                $username = $dashboardDirs->key();
            }

            Logger::info('Migrating INI dashboards for user "%s" to database...', $username);

            try {
                $this->migrateFromIni($dashboardDir . '/dashboard.ini');

                if ($deleteLegacyFiles) {
                    unlink($dashboardDir . '/dashboard.ini');
                }
            } catch (NotReadableError $err) {
                if ($err->getPrevious() !== null) {
                    Logger::error('%s: %s', $err->getMessage(), $err->getPrevious()->getMessage());
                } else {
                    Logger::error($err->getMessage());
                }

                $rc = 128;
            }
        }

        if ($rc > 0) {
            Logger::error('Failed to migrate some user dashboards');
            exit($rc);
        }

        Logger::info('Successfully migrated all local user dashboards to database');
    }
}
