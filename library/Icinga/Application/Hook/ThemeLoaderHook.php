<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

/**
 * Provide an implementation of this hook to dynamically provide themes.
 * Note that only the first registered hook is utilized. Also note that
 * for ordinary themes this hook is not required. Place such in your
 * module's theme path: <module-path>/public/css/themes
 */
abstract class ThemeLoaderHook
{
    /**
     * Get the path for the given theme
     *
     * @param ?string $theme
     *
     * @return ?string The path or NULL if the theme is unknown
     */
    abstract public function getThemeFile(?string $theme): ?string;
}
