<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Loader;

use DirectoryIterator;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Security\Csp\AttributedCsp;
use Icinga\Security\Csp\Reason\NavigationCspReason;
use Icinga\User;
use Icinga\Web\Navigation\Navigation;
use ipl\Web\Common\Csp;
use ipl\Web\Url;

/**
 * Loads CSP directives for navigation items that have an external URL.
 * The CSP directive allows the iframe to be embedded on the page.
 */
class NavigationCspLoader implements CspLoader
{
    /**
     * Load CSP directives for user navigation items that have an external URL
     *
     * @param string $type The navigation type
     * @param array $typeConfig The navigation type configuration
     * @param User $user User to check access for
     *
     * @return AttributedCsp[]
     */
    protected function loadUserConfig(string $type, array $typeConfig, User $user): array
    {
        $config = Config::navigation($type, $user->getUsername());
        if ($config->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($config as $sectionName => $section) {
            $parsed = $this->parseNavigationSection(
                $type,
                $typeConfig,
                $sectionName,
                $section,
                false,
                $user->getUsername()
            );
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    /**
     * Load CSP directives for shared navigation items that have an external URL
     *
     * @param string $type The navigation type
     * @param array $typeConfig The navigation type configuration
     * @param ?User $user User to check access for. If null, all shared
     *   navigation items are loaded.
     *
     * @return array
     */
    protected function loadSharedConfig(string $type, array $typeConfig, ?User $user): array
    {
        $config = Config::navigation($type);
        if ($config->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($config as $sectionName => $section) {
            if ($user !== null && ! $this->hasAccessToSharedNavigationItem($section, $config, $user)) {
                continue;
            }

            $parsed = $this->parseNavigationSection(
                $type,
                $typeConfig,
                $sectionName,
                $section,
                true,
                $user?->getUsername()
            );
            if ($parsed !== null) {
                $result[] = $parsed;
            }
        }

        return $result;
    }

    /**
     * Parse a navigation section and return an AttributedCsp if the section is valid and should be loaded
     *
     * @param string $type First level navigation type
     * @param array $typeConfig First level navigation type configuration
     * @param string $sectionName The name of the ini section
     * @param ConfigObject $section The navigation section
     * @param bool $isShared Whether the section is shared
     * @param ?string $username The username of the user, if the section is bound to a user
     *
     * @return ?AttributedCsp
     */
    protected function parseNavigationSection(
        string $type,
        array $typeConfig,
        string $sectionName,
        ConfigObject $section,
        bool $isShared,
        ?string $username
    ): ?AttributedCsp {
        if ($section->isEmpty()
            || $section->get('target') === '_blank'
            || $section->get('url') === null
            || filter_var($section['url'], FILTER_VALIDATE_URL) === false
        ) {
            return null;
        }

        $owner = $section->get('owner');
        $url = Url::fromPath($section['url']);
        $cspUrl = $url->getScheme() . '://' . $url->getHost();
        if (($port = $url->getPort()) !== null) {
            $cspUrl .= ':' . $port;
        }

        $parent = $section->get('parent');

        $csp = new Csp();
        $csp->add('frame-src', $cspUrl);
        return new AttributedCsp($csp, new NavigationCspReason(
            $type,
            $typeConfig,
            $parent,
            $sectionName,
            $isShared,
            $username ?? $owner,
        ));
    }

    public function loadForAllUsers(): array
    {
        $result = [];
        $navigationTypes = Navigation::getItemTypeConfiguration();
        foreach ($navigationTypes as $type => $typeConfig) {
            $result = array_merge($result, $this->loadSharedConfig($type, $typeConfig, null));
            $preferencesDir = Config::resolvePath('preferences');
            if (! is_dir($preferencesDir)) {
                continue;
            }

            foreach (new DirectoryIterator($preferencesDir) as $userDir) {
                if ($userDir->isDot() || ! $userDir->isDir()) {
                    continue;
                }

                $result = array_merge(
                    $result,
                    $this->loadUserConfig($type, $typeConfig, new User($userDir->getFilename())),
                );
            }
        }

        return $result;
    }

    public function loadForUser(User $user): array
    {
        $result = [];
        $navigationTypes = Navigation::getItemTypeConfiguration();
        foreach ($navigationTypes as $type => $typeConfig) {
            $result = array_merge($result, $this->loadSharedConfig($type, $typeConfig, $user));
            $result = array_merge($result, $this->loadUserConfig($type, $typeConfig, $user));
        }

        return $result;
    }

    /**
     * Check whether the user has access to a shared navigation item
     *
     * Also handles inheritance of access restrictions. This method mimics the
     * behavior of {@see \Icinga\Application\Web::hasAccessToSharedNavigationItem()}.
     *
     * @param ConfigObject $config The navigation item configuration
     * @param Config $navConfig The navigation configuration
     * @param User $user The user to check access for
     *
     * @return bool
     */
    private function hasAccessToSharedNavigationItem(ConfigObject $config, Config $navConfig, User $user): bool
    {
        if (isset($config['owner']) && strtolower($config['owner']) === strtolower($user->getUsername())) {
            return true;
        }

        if (isset($config['parent']) && $navConfig->hasSection($config['parent'])) {
            $parentConfig = $navConfig->getSection($config['parent']);

            return $this->hasAccessToSharedNavigationItem($parentConfig, $navConfig, $user);
        }

        if (isset($config['users'])) {
            $users = array_map(trim(...), explode(',', strtolower($config['users'])));
            if (in_array('*', $users, true) || in_array(strtolower($user->getUsername()), $users, true)) {
                return true;
            }
        }

        if (isset($config['groups'])) {
            $groups = array_map(trim(...), explode(',', strtolower($config['groups'])));
            if (in_array('*', $groups, true)) {
                return true;
            }

            $userGroups = array_map(strtolower(...), $user->getGroups());
            $matches = array_intersect($userGroups, $groups);
            if (! empty($matches)) {
                return true;
            }
        }

        return false;
    }
}
