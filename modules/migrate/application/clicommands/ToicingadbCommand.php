<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Migrate\Clicommands;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Data\ConfigObject;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\Module\Icingadb\Compat\UrlMigrator;
use Icinga\Util\DirectoryIterator;
use Icinga\Web\Request;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class ToicingadbCommand extends Command
{
    public function init(): void
    {
        if (! Icinga::app()->getModuleManager()->hasEnabled('icingadb')) {
            Logger::error('Icinga DB module is not enabled. Please verify that the module is installed and enabled.');
            exit;
        }
    }

    /**
     * Migrate local user monitoring navigation items to the Icinga DB Web actions
     *
     * USAGE
     *
     *  icingacli migrate toicingadb navigation [options]
     *
     * REQUIRED OPTIONS:
     *
     *  --user=<username>  Migrate monitoring navigation items only for
     *                     the given user or matching all similar users
     *                     if a wildcard is used. (* matches all users)
     *
     * OPTIONS:
     *
     *  --override         Override the existing Icinga DB navigation items
     *
     *  --delete           Remove the legacy files after successfully
     *                     migrated the navigation items.
     */
    public function navigationAction(): void
    {
        $preferencesPath = Config::resolvePath('preferences');
        $sharedNavigation = Config::resolvePath('navigation');
        if (! file_exists($preferencesPath) && ! file_exists($sharedNavigation)) {
            Logger::info('There are no local user navigation items to migrate');
            return;
        }

        $rc = 0;
        /** @var string $user */
        $user = $this->params->getRequired('user');
        $directories = new DirectoryIterator($preferencesPath);

        foreach ($directories as $directory) {
            /** @var string $username */
            $username = $directories->key() === false ? '' : $directories->key();
            if (fnmatch($user, $username) === false) {
                continue;
            }

            $hostActions = $this->readFromIni($directory . '/host-actions.ini', $rc);
            $serviceActions = $this->readFromIni($directory . '/service-actions.ini', $rc);

            Logger::info('Migrating monitoring navigation items for user "%s" to the Icinga DB Web actions', $username);

            if (! $hostActions->isEmpty()) {
                $this->migrateNavigationItems($hostActions, $directory . '/icingadb-host-actions.ini', false, $rc);
            }

            if (! $serviceActions->isEmpty()) {
                $this->migrateNavigationItems(
                    $serviceActions,
                    $directory . '/icingadb-service-actions.ini',
                    false,
                    $rc
                );
            }
        }

        // Start migrating shared navigation items
        $hostActions = $this->readFromIni($sharedNavigation . '/host-actions.ini', $rc);
        $serviceActions = $this->readFromIni($sharedNavigation . '/service-actions.ini', $rc);

        Logger::info('Migrating shared monitoring navigation items to the Icinga DB Web actions');

        if (! $hostActions->isEmpty()) {
            $this->migrateNavigationItems($hostActions, $sharedNavigation . '/icingadb-host-actions.ini', true, $rc);
        }

        if (! $serviceActions->isEmpty()) {
            $this->migrateNavigationItems(
                $serviceActions,
                $sharedNavigation . '/icingadb-service-actions.ini',
                true,
                $rc
            );
        }

        if ($rc > 0) {
            Logger::error('Failed to migrate some monitoring navigation items');
            exit($rc);
        }

        Logger::info('Successfully migrated all local user monitoring navigation items');
    }

    /**
     * Migrate the given config to the given new config path
     *
     * @param Config $config
     * @param string $path
     * @param bool   $shared
     * @param int    $rc
     */
    private function migrateNavigationItems($config, $path, $shared, &$rc): void
    {
        /** @var string $owner */
        $owner = $this->params->getRequired('user');
        $deleteLegacyFiles = $this->params->get('delete');
        $override = $this->params->get('override');
        $newConfig = $this->readFromIni($path, $rc);

        /** @var ConfigObject $configObject */
        foreach ($config->getConfigObject() as $configObject) {
            // Change the config type from "host-action" to icingadb's new action
            if ($shared && ! fnmatch($owner, $configObject->get('owner'))) {
                continue;
            }

            if (strpos($path, 'icingadb-host-actions') !== false) {
                $configObject->type = 'icingadb-host-action';
            } else {
                $configObject->type = 'icingadb-service-action';
            }

            /** @var ?string $urlString */
            $urlString = $configObject->get('url');
            if ($urlString !== null) {
                $url = Url::fromPath($urlString, [], new Request());

                try {
                    $urlString = UrlMigrator::transformUrl($url)->getAbsoluteUrl();
                    $configObject->url = rawurldecode($urlString);
                } catch (\InvalidArgumentException $err) {
                    // Do nothing
                }
            }

            /** @var ?string $legacyFilter */
            $legacyFilter = $configObject->get('filter');
            if ($legacyFilter !== null) {
                $filter = QueryString::parse($legacyFilter);
                $filter = UrlMigrator::transformFilter($filter);
                if ($filter !== false) {
                    $configObject->filter = rawurldecode(QueryString::render($filter));
                } else {
                    unset($configObject->filter);
                }
            }

            $section = $config->key();

            if (! $newConfig->hasSection($section) || $override) {
                /** @var string $owner */
                $owner = $configObject->get('owner');
                /** @var string $type */
                $type = $configObject->get('type');
                $oldPath = $shared
                    ? sprintf(
                        '%s/%s/%ss.ini',
                        Config::resolvePath('preferences'),
                        $owner,
                        $type
                    )
                    : sprintf(
                        '%s/%ss.ini',
                        Config::resolvePath('navigation'),
                        $type
                    );

                $oldConfig = $this->readFromIni($oldPath, $rc);

                if ($override && $oldConfig->hasSection($section)) {
                    $oldConfig->removeSection($section);
                    $oldConfig->saveIni();
                }

                if (! $oldConfig->hasSection($section)) {
                    $newConfig->setSection($section, $configObject);
                }
            }
        }

        try {
            if (! $newConfig->isEmpty()) {
                $newConfig->saveIni();

                // Remove the legacy file only if explicitly requested
                if ($deleteLegacyFiles) {
                    unlink($config->getConfigFile());
                }
            }
        } catch (NotWritableError $error) {
            Logger::error('%s: %s', $error->getMessage(), $error->getPrevious()->getMessage());
            $rc = 256;
        }
    }

    /**
     * Get the navigation items config from the given ini path
     *
     * @param string $path Absolute path of the ini file
     * @param int $rc      The return code used to exit the action
     *
     * @return Config
     */
    private function readFromIni($path, &$rc)
    {
        try {
            $config = Config::fromIni($path);
        } catch (NotReadableError $error) {
            if ($error->getPrevious() !== null) {
                Logger::error('%s: %s', $error->getMessage(), $error->getPrevious()->getMessage());
            } else {
                Logger::error($error->getMessage());
            }

            $config = new Config();
            $rc = 128;
        }

        return $config;
    }
}
