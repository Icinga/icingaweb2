<?php

// SPDX-FileCopyrightText: 2022 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

/**
 * Provide an implementation of this hook to dynamically provide themes.
 * Note that only the first registered hook is utilized. Also note that
 * for ordinary themes this hook is not required. Place such in your
 * module's theme path: <module-path>/public/css/themes
 */
abstract class ThemeLoaderHook
{
    use HookEssentials;

    final protected static function getHookName(): string
    {
        return 'ThemeLoader';
    }

    /**
     * Get the path for the given theme
     *
     * @param ?string $theme
     *
     * @return ?string The path or NULL if the theme is unknown
     */
    abstract public function getThemeFile(?string $theme): ?string;
}
