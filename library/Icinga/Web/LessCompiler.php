<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web;

use Exception;
use Icinga\Application\Logger;
use ipl\Web\Less\CssVarVisitor;
use ipl\Web\Less\DetachedRulesetCallVisitor;
use ipl\Web\Less\WikimediaLessCompiler;

/**
 * Compile LESS into CSS
 */
class LessCompiler
{
    /**
     * Array of LESS files
     *
     * @var string[]
     */
    protected $lessFiles = array();

    /**
     * Array of module LESS files indexed by module names
     *
     * @var array[]
     */
    protected $moduleLessFiles = array();

    /**
     * LESS source
     *
     * @var string
     */
    protected $source;

    /**
     * Path of the LESS theme
     *
     * @var string
     */
    protected $theme;

    /**
     * Path of the LESS theme mode
     *
     * @var string
     */
    protected $themeMode;

    /**
     * Add a Web 2 LESS file
     *
     * @param   string  $lessFile   Path to the LESS file
     *
     * @return  $this
     */
    public function addLessFile($lessFile)
    {
        $this->lessFiles[] = realpath($lessFile);
        return $this;
    }

    /**
     * Add a module LESS file
     *
     * @param   string  $moduleName Name of the module
     * @param   string  $lessFile   Path to the LESS file
     *
     * @return  $this
     */
    public function addModuleLessFile($moduleName, $lessFile)
    {
        if (! isset($this->moduleLessFiles[$moduleName])) {
            $this->moduleLessFiles[$moduleName] = array();
        }
        $this->moduleLessFiles[$moduleName][] = realpath($lessFile);
        return $this;
    }

    /**
     * Get the list of LESS files added to the compiler
     *
     * @return string[]
     */
    public function getLessFiles()
    {
        $lessFiles = $this->lessFiles;

        foreach ($this->moduleLessFiles as $moduleLessFiles) {
            $lessFiles = array_merge($lessFiles, $moduleLessFiles);
        }

        if ($this->theme !== null) {
            $lessFiles[] = $this->theme;
        }

        if ($this->themeMode !== null) {
            $lessFiles[] = $this->themeMode;
        }

        return $lessFiles;
    }

    /**
     * Set the path to the LESS theme
     *
     * @param   ?string  $theme  Path to the LESS theme
     *
     * @return  $this
     */
    public function setTheme($theme)
    {
        if ($theme === null || (is_file($theme) && is_readable($theme))) {
            $this->theme = $theme;
        } else {
            Logger::error('Can\t load theme %s. Make sure that the theme exists and is readable', $theme);
        }
        return $this;
    }

    /**
     * Set the path to the LESS theme mode
     *
     * @param   string  $themeMode  Path to the LESS theme mode
     *
     * @return  $this
     */
    public function setThemeMode($themeMode)
    {
        if (is_file($themeMode) && is_readable($themeMode)) {
            $this->themeMode = $themeMode;
        } else {
            Logger::error('Can\t load theme mode %s. Make sure that the theme mode exists and is readable', $themeMode);
        }
        return $this;
    }

    /**
     * Render to CSS
     *
     * @param bool $minify Whether to minify the CSS
     *
     * @return string
     */
    public function render(bool $minify = false): string
    {
        // Use `@import (less)` throughout to force the imported file to be treated as a regular Less file,
        // regardless of its extension. Regular CSS files would leave the import statement in the output.
        $imports = array_map(fn($file) => "@import (less) \"$file\";", $this->lessFiles);

        foreach (array_filter($this->moduleLessFiles) as $name => $files) {
            $imports = array_merge($imports, [
                ".icinga-module.module-$name {",
                ...array_map(fn($file) => "    @import (less) \"$file\";", $files),
                "}\n",
            ]);
        }

        if ($this->theme !== null) {
            $imports[] = "@import (less) \"$this->theme\";";
        }

        if ($this->themeMode !== null) {
            $imports[] = "@import (less) \"$this->themeMode\";";
        }

        $less = implode("\n", $imports);

        $lightModeTemplate = <<<'LESS'
@media (min-height: @prefer-light-color-scheme), print,
(prefers-color-scheme: light) and (min-height: @enable-color-preference) {
    {ruleset}
}
LESS;
        $compiler = new WikimediaLessCompiler([
            // Despite its name, relativeUrls doesn't preserve relative URLs. It rewrites
            // them to the resolved path of the Less file they appear in. We concatenate
            // all Less files via @import, which also lets the parser report the affected
            // file in error messages. Enabling relativeUrls would turn e.g.
            // "../img/icinga-logo.svg" into an absolute resolved path like
            // /public/css/icinga/img/icinga-logo.svg, which is not publicly accessible.
            'relativeUrls' => false,
            'math'         => 'always',
            'plugins'      => [
                new CssVarVisitor(),
                new DetachedRulesetCallVisitor('light-mode', $lightModeTemplate),
            ],
        ]);

        try {
            return preg_replace(
                '/(\.icinga-module\.module-[^\s]+) (#layout\.[^\s]+)/m',
                '\2 \1',
                $compiler->compile($less, $minify),
            );
        } catch (Exception $e) {
            return "\n" . $e->getMessage() . "\n\nStack trace:\n" . $e->getTraceAsString();
        }
    }
}
