<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\User;
use Icinga\User\Preferences\Store\IniStore;
use Icinga\User\Preferences\Store\DbStore;
use Icinga\Util\DirectoryIterator;

class PreferencesCommand extends Command
{
    /**
     * Migrate local INI user preferences to a database
     *
     * USAGE
     *
     *  icingacli migrate preferences [options]
     *
     * OPTIONS:
     *
     *  --resource=<resource-name>  The resource to use, if no current database config backend is configured.
     *  --no-set-config-backend     Do not set the given resource as config backend automatically
     */
    public function indexAction()
    {
        $resource = Config::app()->get('global', 'config_resource');
        if (empty($resource)) {
            $resource = $this->params->getRequired('resource');
        }

        $resourceConfig = ResourceFactory::getResourceConfig($resource);
        if ($resourceConfig->db === 'mysql') {
            $resourceConfig->charset = 'utf8mb4';
        }

        $connection = ResourceFactory::createResource($resourceConfig);

        $preferencesPath = Config::resolvePath('preferences');
        if (! file_exists($preferencesPath)) {
            Logger::info('There are no local user preferences to migrate');
            return;
        }

        $rc = 0;

        $preferenceDirs = new DirectoryIterator($preferencesPath);
        foreach ($preferenceDirs as $preferenceDir) {
            if (! is_dir($preferenceDir)) {
                continue;
            }

            $userName = basename($preferenceDir);

            Logger::info('Migrating INI preferences for user "%s" to database...', $userName);

            $iniStore = new IniStore(new ConfigObject(['location' => $preferencesPath]), new User($userName));
            $dbStore = new DbStore(new ConfigObject(['connection' => $connection]), new User($userName));

            try {
                $dbStore->load();
                $dbStore->save(new User\Preferences($iniStore->load()));
            } catch (NotReadableError $e) {
                if ($e->getPrevious() !== null) {
                    Logger::error('%s: %s', $e->getMessage(), $e->getPrevious()->getMessage());
                } else {
                    Logger::error($e->getMessage());
                }

                $rc = 128;
            } catch (NotWritableError $e) {
                Logger::error('%s: %s', $e->getMessage(), $e->getPrevious()->getMessage());
                $rc = 256;
            }
        }

        if ($rc > 0) {
            Logger::error('Failed to migrate some user preferences');
            exit($rc);
        }

        if ($this->params->has('resource') && ! $this->params->has('no-set-config-backend')) {
            $appConfig = Config::app();
            $globalConfig = $appConfig->getSection('global');
            $globalConfig['config_backend'] = 'db';
            $globalConfig['config_resource'] = $resource;

            try {
                $appConfig->saveIni();
            } catch (NotWritableError $e) {
                Logger::error('Failed to update general configuration: %s', $e->getMessage());
                exit(256);
            }
        }

        Logger::info('Successfully migrated all local user preferences to database');
    }
}
