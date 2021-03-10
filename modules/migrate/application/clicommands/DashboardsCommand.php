<?php

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Config;
use Icinga\Cli\Command;
use Icinga\Common\Database;
use Icinga\Exception\NotReadableError;
use Icinga\Util\DirectoryIterator;
use ipl\Sql\Select;

class DashboardsCommand extends Command
{
    use Database;

    /**
     * Name of the default home
     *
     * @var string
     */
    const DEFAULT_HOME = 'Default Home';

    /**
     * Default user of the "Default Home"
     *
     * @var string
     */
    const DEFAULT_HOME_USER = 'icingaweb2';

    /**
     * Migrates all existing dashboards that are located in
     * dashboard.ini files to the database
     *
     * USAGE
     *
     *  icingacli migrate dashboards
     */
    public function indexAction()
    {
        $config = Config::resolvePath('dashboards');

        if (DirectoryIterator::isReadable($config)) {
            $directories = new DirectoryIterator($config);

            foreach ($directories as $directory) {
                $directory .= '/dashboard.ini';

                $this->loadUserDashboardsFromFile($directories->key(), $directory);
            }
        }
    }

    /**
     * Load user dashboards from the given config file
     *
     * @param string $username
     *
     * @param string $file
     */
    private function loadUserDashboardsFromFile($username, $file)
    {
        try {
            $config = Config::fromIni($file);
        } catch (NotReadableError $error) {
            return;
        }

        if (! count($config)) {
            return;
        }

        $db = $this->getDb();
        $defaultHome = $db->select((new Select())
            ->columns('id')
            ->from('dashboard_home')
            ->where([
                'name = ?'  => self::DEFAULT_HOME,
                'owner = ?' => self::DEFAULT_HOME_USER
            ]))->fetch();

        if (! $defaultHome) {
            $db->insert('dashboard_home', [
                'name'  => self::DEFAULT_HOME,
                'owner' => self::DEFAULT_HOME_USER
            ]);

            $homeId = $db->lastInsertId();
        } else {
            $homeId = $defaultHome->id;
        }

        $panes = [];
        foreach ($config as $key => $part) {
            if (strpos($key, '.') === false) {
                $paneId = sha1($username . self::DEFAULT_HOME . $key, true);
                $searchPane = $db->select((new Select())
                    ->columns('id')
                    ->from('dashboard')
                    ->where([
                        'id = ?'        => $paneId,
                        'home_id = ?'   => $homeId
                    ]))->fetch();

                if ($searchPane) {
                    $db->update('dashboard', [
                        'owner' => $username,
                        'name'  => $key,
                        'label' => $part->get('title', $key)
                    ], ['id = ?' => $paneId, 'home_id = ?' => $homeId]);
                } else {
                    $db->insert('dashboard', [
                        'id'        => $paneId,
                        'home_id'   => $homeId,
                        'owner'     => $username,
                        'name'      => $key,
                        'label'     => $part->get('title', $key)
                    ]);
                }

                $panes[$key] = $paneId;
            } else {
                list($paneName, $dashletName) = explode('.', $key, 2);
                if (! array_key_exists($paneName, $panes)) {
                    continue;
                }

                $dashletId = sha1($username . self::DEFAULT_HOME . $paneName . $dashletName, true);
                $searchDashlet = $db->select((new Select())
                    ->columns('id')
                    ->from('dashlet')
                    ->where([
                        'id = ?'            => $dashletId,
                        'dashboard_id = ?'  => $panes[$paneName]
                    ]))->fetch();

                if ($searchDashlet) {
                    $db->update('dashlet', [
                        'owner' => $username,
                        'name'  => $dashletName,
                        'label' => $part->get('title', $dashletName),
                        'url'   => $part->get('url')
                    ], ['id = ?' => $dashletId, 'dashboard_id = ?' => $panes[$paneName]]);
                } else {
                    $db->insert('dashlet', [
                        'id'            => $dashletId,
                        'dashboard_id'  => $panes[$paneName],
                        'owner'         => $username,
                        'name'          => $dashletName,
                        'label'         => $part->get('title', $dashletName),
                        'url'           => $part->get('url')
                    ]);
                }
            }
        }
    }
}
