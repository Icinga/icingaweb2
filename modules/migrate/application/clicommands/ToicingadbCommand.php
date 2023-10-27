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
use ipl\Stdlib\Filter;

class ToicingadbCommand extends Command
{
    public function init(): void
    {
        $moduleManager = Icinga::app()->getModuleManager();
        if (! $moduleManager->hasEnabled('icingadb')) {
            Logger::error('Icinga DB module is not enabled. Please verify that the module is installed and enabled.');
            exit;
        }

        $icingadbVersion = $moduleManager->getModule('icingadb')->getVersion();
        if (version_compare($icingadbVersion, '1.1.0', '<')) {
            Logger::error(
                'Required Icinga DB Web version 1.1.0 or greater to run this migration. '
                . 'Please upgrade your Icinga DB Web module.'
            );
            exit;
        }

        Logger::getInstance()->setLevel(Logger::INFO);
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
     *                     the given user or all similar users if a
     *                     wildcard is used. (* matches all users)
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
            $icingadbHostActions = $this->readFromIni($directory . '/icingadb-host-actions.ini', $rc);
            $icingadbServiceActions = $this->readFromIni($directory . '/icingadb-service-actions.ini', $rc);

            Logger::info(
                'Transforming legacy wildcard filters of existing Icinga DB Web actions for user "%s"',
                $username
            );

            if (! $icingadbHostActions->isEmpty()) {
                $this->migrateNavigationItems($icingadbHostActions, false, $rc);
            }

            if (! $icingadbServiceActions->isEmpty()) {
                $this->migrateNavigationItems(
                    $icingadbServiceActions,
                    false,
                    $rc
                );
            }

            Logger::info('Migrating monitoring navigation items for user "%s" to the Icinga DB Web actions', $username);

            if (! $hostActions->isEmpty()) {
                $this->migrateNavigationItems($hostActions, false, $rc, $directory . '/icingadb-host-actions.ini');
            }

            if (! $serviceActions->isEmpty()) {
                $this->migrateNavigationItems(
                    $serviceActions,
                    false,
                    $rc,
                    $directory . '/icingadb-service-actions.ini'
                );
            }
        }

        // Start migrating shared navigation items
        $hostActions = $this->readFromIni($sharedNavigation . '/host-actions.ini', $rc);
        $serviceActions = $this->readFromIni($sharedNavigation . '/service-actions.ini', $rc);
        $icingadbHostActions = $this->readFromIni($sharedNavigation . '/icingadb-host-actions.ini', $rc);
        $icingadbServiceActions = $this->readFromIni($sharedNavigation . '/icingadb-service-actions.ini', $rc);

        Logger::info('Transforming legacy wildcard filters of existing shared Icinga DB Web actions');

        if (! $icingadbHostActions->isEmpty()) {
            $this->migrateNavigationItems($icingadbHostActions, true, $rc);
        }

        if (! $icingadbServiceActions->isEmpty()) {
            $this->migrateNavigationItems(
                $icingadbServiceActions,
                true,
                $rc
            );
        }

        Logger::info('Migrating shared monitoring navigation items to the Icinga DB Web actions');

        if (! $hostActions->isEmpty()) {
            $this->migrateNavigationItems($hostActions, true, $rc, $sharedNavigation . '/icingadb-host-actions.ini');
        }

        if (! $serviceActions->isEmpty()) {
            $this->migrateNavigationItems(
                $serviceActions,
                true,
                $rc,
                $sharedNavigation . '/icingadb-service-actions.ini'
            );
        }

        if ($rc > 0) {
            Logger::error('Failed to migrate some monitoring navigation items');
            exit($rc);
        }

        Logger::info('Successfully migrated all local user monitoring navigation items');
    }


    /**
     * Migrate monitoring restrictions and permissions in a role to Icinga DB Web restrictions and permissions
     *
     * USAGE
     *
     *  icingacli migrate toicingadb role [options]
     *
     * OPTIONS:
     *
     *  --group=<groupname>  Migrate monitoring restrictions and permissions for all roles,
     *                       the given group or the groups matching the given
     *                       group belongs to.
     *                       (wildcard * migrates monitoring restrictions and permissions
     *                       for all roles)
     *
     *  --role=<rolename>    Migrate monitoring restrictions and permissions for the
     *                       given role or all the matching roles.
     *                       (wildcard * migrates monitoring restrictions and permissions
     *                       for all roles)
     *
     *  --override          Override the existing Icinga DB restrictions and permissions
     */
    public function roleAction(): void
    {
        /** @var ?bool $override */
        $override = $this->params->get('override');

        /** @var ?string $groupName */
        $groupName = $this->params->get('group');
        /** @var ?string $roleName */
        $roleName = $this->params->get('role');

        if ($roleName === null && $groupName === null) {
            $this->fail("One of the parameters 'group' or 'role' must be supplied");
        } elseif ($roleName !== null && $groupName !== null) {
            $this->fail("Use either 'group' or 'role'. Both cannot be used as role overrules group.");
        }

        $rc = 0;
        $restrictions = Config::$configDir . '/roles.ini';
        $rolesConfig = $this->readFromIni($restrictions, $rc);
        $monitoringRestriction = 'monitoring/filter/objects';
        $monitoringPropertyBlackList = 'monitoring/blacklist/properties';
        $icingadbRestrictions = [
            'objects'  => 'icingadb/filter/objects',
            'hosts'    => 'icingadb/filter/hosts',
            'services' => 'icingadb/filter/services'
        ];

        $icingadbPropertyDenyList = 'icingadb/denylist/variables';
        Logger::info('Start monitoring restrictions migration');
        foreach ($rolesConfig as $name => $role) {
            /** @var string[] $role */
            $role = iterator_to_array($role);

            if ($roleName === '*' || $groupName === '*') {
                $updateRole = $this->shouldUpdateRole($role, $override);
            } elseif ($roleName !== null && fnmatch($roleName, $name)) {
                $updateRole = $this->shouldUpdateRole($role, $override);
            } elseif ($groupName !== null && isset($role['groups'])) {
                $roleGroups = array_map('trim', explode(',', $role['groups']));
                $updateRole = false;
                foreach ($roleGroups as $roleGroup) {
                    if (fnmatch($groupName, $roleGroup)) {
                        $updateRole = $this->shouldUpdateRole($role, $override);
                        break;
                    }
                }
            } else {
                $updateRole = false;
            }

            if ($updateRole) {
                if (isset($role[$monitoringRestriction])) {
                    Logger::info(
                        'Migrating monitoring restriction filter for role "%s" to the Icinga DB Web restrictions',
                        $name
                    );
                    $transformedFilter = UrlMigrator::transformFilter(
                        QueryString::parse($role[$monitoringRestriction])
                    );

                    if ($transformedFilter) {
                        $role[$icingadbRestrictions['objects']] = rawurldecode(
                            QueryString::render($transformedFilter)
                        );
                    }
                }

                if (isset($role[$monitoringPropertyBlackList])) {
                    Logger::info(
                        'Migrating monitoring blacklisted properties for role "%s" to the Icinga DB Web deny list',
                        $name
                    );

                    $icingadbProperties = [];
                    foreach (explode(',', $role[$monitoringPropertyBlackList]) as $property) {
                        $icingadbProperties[] = preg_replace('/^(?:host|service)\.vars\./i', '', $property, 1);
                    }

                    $role[$icingadbPropertyDenyList] = str_replace(
                        '**',
                        '*',
                        implode(',', array_unique($icingadbProperties))
                    );
                }

                if (isset($role['permissions'])) {
                    $updatedPermissions = [];
                    Logger::info(
                        'Migrating monitoring permissions for role "%s" to the Icinga DB Web permissions',
                        $name
                    );

                    if (strpos($role['permissions'], 'monitoring')) {
                        $monitoringProtection = Config::module('monitoring')
                            ->get('security', 'protected_customvars');

                        if ($monitoringProtection !== null) {
                            $role['icingadb/protect/variables'] = $monitoringProtection;
                        }
                    }
                    
                    foreach (explode(',', $role['permissions']) as $permission) {
                        if (str_contains($permission, 'icingadb')) {
                            continue;
                        } elseif (fnmatch('monitoring/command*', $permission)) {
                            $updatedPermissions[] = $permission;
                            $updatedPermissions[] = str_replace('monitoring', 'icingadb', $permission);
                        } elseif ($permission === 'no-monitoring/contacts') {
                            $updatedPermissions[] = $permission;
                            $role['icingadb/denylist/routes'] = 'users,usergroups';
                        } else {
                            $updatedPermissions[] = $permission;
                        }
                    }

                    $role['permissions'] = implode(',', $updatedPermissions);
                }

                if (isset($role['refusals']) && is_string($role['refusals'])) {
                    $updatedRefusals = [];
                    Logger::info(
                        'Migrating monitoring refusals for role "%s" to the Icinga DB Web refusals',
                        $name
                    );

                    foreach (explode(',', $role['refusals']) as $refusal) {
                        if (str_contains($refusal, 'icingadb')) {
                            continue;
                        } elseif (fnmatch('monitoring/command*', $refusal)) {
                            $updatedRefusals[] = $refusal;
                            $updatedRefusals[] = str_replace('monitoring', 'icingadb', $refusal);
                        } else {
                            $updatedRefusals[] = $refusal;
                        }
                    }

                    $role['refusals'] = implode(',', $updatedRefusals);
                }
            }

            foreach ($icingadbRestrictions as $object => $icingadbRestriction) {
                if (isset($role[$icingadbRestriction]) && is_string($role[$icingadbRestriction])) {
                    $filter = QueryString::parse($role[$icingadbRestriction]);
                    $filter = $this->transformLegacyWildcardFilter($filter);

                    if ($filter) {
                        $filter = rawurldecode(QueryString::render($filter));
                        if ($filter !== $role[$icingadbRestriction]) {
                            Logger::info(
                                'Icinga Db Web restriction of role "%s" for %s changed from "%s" to "%s"',
                                $name,
                                $object,
                                $role[$icingadbRestriction],
                                $filter
                            );

                            $role[$icingadbRestriction] = $filter;
                        }
                    }
                }
            }

            $rolesConfig->setSection($name, $role);
        }

        try {
            $rolesConfig->saveIni();
        } catch (NotWritableError $error) {
            Logger::error('%s: %s', $error->getMessage(), $error->getPrevious()->getMessage());
            Logger::error('Failed to migrate monitoring restrictions');
            exit(256);
        }

        Logger::info('Successfully migrated monitoring restrictions and permissions in roles');
    }

    /**
     * Checks if the given role should be updated
     *
     * @param string[] $role
     * @param bool     $override
     *
     * @return bool
     */
    private function shouldUpdateRole(array $role, ?bool $override): bool
    {
        return ! (
                isset($role['icingadb/filter/objects'])
                || isset($role['icingadb/filter/hosts'])
                || isset($role['icingadb/filter/services'])
                || isset($role['icingadb/denylist/routes'])
                || isset($role['icingadb/denylist/variables'])
                || isset($role['icingadb/protect/variables'])
                || (isset($role['permissions']) && str_contains($role['permissions'], 'icingadb'))
            )
            || $override;
    }

    /**
     * Migrate the monitoring dashboards to Icinga DB Web dashboards for all the matched users
     *
     * USAGE
     *
     *  icingacli migrate toicingadb dasboard [options]
     *
     * REQUIRED OPTIONS:
     *
     *  --user=<username>  Migrate monitoring dashboards for all the
     *                     users that are matched. (* all users)
     *
     * OPTIONS:
     *
     *  --no-backup        Migrate without creating a backup. (By Default
     *                     a backup for monitoring dashboards is created)
     */
    public function dashboardAction(): void
    {
        $dashboardsPath = Config::resolvePath('dashboards');
        if (! file_exists($dashboardsPath)) {
            Logger::info('There are no dashboards to migrate');
            return;
        }

        /** @var string $user */
        $user = $this->params->getRequired('user');
        $noBackup = $this->params->get('no-backup');

        $rc = 0;
        $directories = new DirectoryIterator($dashboardsPath);

        Logger::info(
            'Start monitoring dashboards migration',
            $user
        );

        foreach ($directories as $directory) {
            /** @var string $userName */
            $userName = $directories->key() === false ? '' : $directories->key();
            if (fnmatch($user, $userName) === false) {
                continue;
            }

            $dashboardsConfig = $this->readFromIni($directory . '/dashboard.ini', $rc);
            $backupConfig = $this->readFromIni($directory . '/dashboard.ini', $rc);

            Logger::info(
                'Migrating monitoring dashboards to Icinga DB Web dashboards for user "%s"',
                $userName
            );

            $changed = false;
            /** @var ConfigObject $dashboardConfig */
            foreach ($dashboardsConfig->getConfigObject() as $name => $dashboardConfig) {
                /** @var ?string $dashboardUrlString */
                $dashboardUrlString = $dashboardConfig->get('url');
                if ($dashboardUrlString !== null) {
                    $dashBoardUrl = Url::fromPath($dashboardUrlString, [], new Request());
                    if (fnmatch('monitoring*', $dashboardUrlString)) {
                        $dashboardConfig->url = rawurldecode(
                            UrlMigrator::transformUrl($dashBoardUrl)->getRelativeUrl()
                        );

                        $changed = true;
                    }

                    if (fnmatch('icingadb*', ltrim($dashboardUrlString, '/'))) {
                        $filter = QueryString::parse($dashBoardUrl->getParams()->toString());
                        $filter = $this->transformLegacyWildcardFilter($filter);
                        if ($filter) {
                            $oldFilterString = $dashBoardUrl->getParams()->toString();
                            $newFilterString = rawurldecode(QueryString::render($filter));

                            if ($oldFilterString !== $newFilterString) {
                                Logger::info(
                                    'Icinga Db Web filter of dashboard "%s" has changed from "%s" to "%s"',
                                    $name,
                                    rawurldecode($dashBoardUrl->getParams()->toString()),
                                    rawurldecode(QueryString::render($filter))
                                );
                                $dashBoardUrl->setParams([]);
                                $dashBoardUrl->setFilter($filter);

                                $dashboardConfig->url = rawurldecode($dashBoardUrl->getRelativeUrl());
                                $changed = true;
                            }
                        }
                    }
                }
            }


            if ($changed && $noBackup === null) {
                $counter = 0;
                while (true) {
                    $filepath = $counter > 0
                        ? $directory . "/dashboard.backup$counter.ini"
                        : $directory . '/dashboard.backup.ini';

                    if (! file_exists($filepath)) {
                        $backupConfig->saveIni($filepath);
                        break;
                    } else {
                        $counter++;
                    }
                }
            }

            try {
                $dashboardsConfig->saveIni();
            } catch (NotWritableError $error) {
                Logger::error('%s: %s', $error->getMessage(), $error->getPrevious()->getMessage());
                $rc = 256;
            }
        }

        if ($rc > 0) {
            Logger::error('Failed to migrate some monitoring dashboards');
            exit($rc);
        }

        Logger::info('Successfully migrated dashboards for all the matched users');
    }

    /**
     * Migrate the given config to the given new config path
     *
     * @param Config $config
     * @param ?string $path
     * @param bool   $shared
     * @param int    $rc
     */
    private function migrateNavigationItems($config, $shared, &$rc, $path = null): void
    {
        /** @var string $owner */
        $owner = $this->params->getRequired('user');
        if ($path === null) {
            $newConfig = $config;
            /** @var ConfigObject $newConfigObject */
            foreach ($newConfig->getConfigObject() as $section => $newConfigObject) {
                /** @var string $configOwner */
                $configOwner = $newConfigObject->get('owner') ?? '';
                if ($shared && ! fnmatch($owner, $configOwner)) {
                    continue;
                }

                /** @var ?string $legacyFilter */
                $legacyFilter = $newConfigObject->get('filter');
                if ($legacyFilter !== null) {
                    $filter = QueryString::parse($legacyFilter);
                    $filter = $this->transformLegacyWildcardFilter($filter);
                    if ($filter) {
                        $filter = rawurldecode(QueryString::render($filter));
                        if ($legacyFilter !== $filter) {
                            $newConfigObject->filter = $filter;
                            $newConfig->setSection($section, $newConfigObject);
                            Logger::info(
                                'Icinga DB Web filter of action "%s" is changed from %s to "%s"',
                                $section,
                                $legacyFilter,
                                $filter
                            );
                        }
                    }
                }
            }
        } else {
            $deleteLegacyFiles = $this->params->get('delete');
            $override = $this->params->get('override');
            $newConfig = $this->readFromIni($path, $rc);

            /** @var ConfigObject $configObject */
            foreach ($config->getConfigObject() as $configObject) {
                // Change the config type from "host-action" to icingadb's new action
                /** @var string $configOwner */
                $configOwner = $configObject->get('owner') ?? '';
                if ($shared && ! fnmatch($owner, $configOwner)) {
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
                    /** @var string $type */
                    $type = $configObject->get('type');
                    $oldPath = $shared
                        ? sprintf(
                            '%s/%s/%ss.ini',
                            Config::resolvePath('preferences'),
                            $configOwner,
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
        }

        try {
            if (! $newConfig->isEmpty()) {
                $newConfig->saveIni();

                // Remove the legacy file only if explicitly requested
                if ($path !== null && $deleteLegacyFiles) {
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

    /**
     * Transform given legacy wirldcard filters
     *
     * @param $filter Filter\Rule
     *
     * @return Filter\Chain|Filter\Condition|null
     */
    private function transformLegacyWildcardFilter(Filter\Rule $filter)
    {
        if ($filter instanceof Filter\Chain) {
            foreach ($filter as $child) {
                $newChild = $this->transformLegacyWildcardFilter($child);
                if ($newChild !== null) {
                    $filter->replace($child, $newChild);
                }
            }

            return $filter;
        } elseif ($filter instanceof Filter\Equal) {
            if (is_string($filter->getValue()) && strpos($filter->getValue(), '*') !== false) {
                return Filter::like($filter->getColumn(), $filter->getValue());
            }
        } elseif ($filter instanceof Filter\Unequal) {
            if (is_string($filter->getValue()) && strpos($filter->getValue(), '*') !== false) {
                return Filter::unlike($filter->getColumn(), $filter->getValue());
            }
        }
    }
}
