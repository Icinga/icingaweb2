<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web;

use Exception;
use ipl\Web\Less\CssVarVisitor;
use ipl\Web\Less\DetachedRulesetCallVisitor;
use ipl\Web\Less\WikimediaLessCompiler;
use RuntimeException;

/**
 * Compile Less into CSS
 */
class LessCompiler
{
    /** @var string[] Core Less and CSS files */
    protected array $lessFiles = [];

    /** @var array<string, string[]> Module Less and CSS files indexed by module name */
    protected array $moduleLessFiles = [];

    protected ?string $source = null;

    /** @var string|null Path to the Less theme file */
    protected ?string $theme = null;

    /** @var string|null Path to the Less theme mode file */
    protected ?string $themeMode = null;

    /**
     * Add a core Less or CSS file
     *
     * @param string $lessFile Path to the Less or CSS file
     *
     * @return $this
     *
     * @throws RuntimeException If the file does not exist or is not readable
     */
    public function addLessFile(string $lessFile): static
    {
        $this->lessFiles[] = $this->resolveReadableFile($lessFile);

        return $this;
    }

    /**
     * Add a module Less or CSS file
     *
     * @param string $moduleName Name of the module
     * @param string $lessFile Path to the Less or CSS file
     *
     * @return $this
     *
     * @throws RuntimeException If the file does not exist or is not readable
     */
    public function addModuleLessFile(string $moduleName, string $lessFile): static
    {
        if (! isset($this->moduleLessFiles[$moduleName])) {
            $this->moduleLessFiles[$moduleName] = [];
        }

        $this->moduleLessFiles[$moduleName][] = $this->resolveReadableFile($lessFile);

        return $this;
    }

    /**
     * Get all file paths registered with the compiler
     *
     * @return string[]
     */
    public function getLessFiles(): array
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
     * Set the path to the Less theme file
     *
     * @param ?string $theme Path to the Less theme file, or null to unset
     *
     * @return $this
     *
     * @throws RuntimeException If the file does not exist or is not readable
     */
    public function setTheme(?string $theme): static
    {
        $this->theme = $theme === null ? null : $this->resolveReadableFile($theme);

        return $this;
    }

    /**
     * Set the path to the Less theme mode file
     *
     * @param string $themeMode Path to the Less theme mode file
     *
     * @return $this
     *
     * @throws RuntimeException If the file does not exist or is not readable
     */
    public function setThemeMode(string $themeMode): static
    {
        $this->themeMode = $this->resolveReadableFile($themeMode);

        return $this;
    }

    /**
     * Resolve a file path to its canonical form and verify it is readable
     *
     * @param string $path Path to the Less or CSS file
     *
     * @return string Canonical path to the file
     *
     * @throws RuntimeException If the file does not exist or is not readable
     */
    protected function resolveReadableFile(string $path): string
    {
        $resolved = realpath($path);
        if ($resolved !== false && is_file($resolved) && is_readable($resolved)) {
            return $resolved;
        }

        throw new RuntimeException("Can't load Less file $path. Make sure that the file exists and is readable");
    }

    /**
     * Compile all registered Less sources to CSS
     *
     * @param bool $minify Whether to minify the output
     *
     * @return string Compiled CSS, or an error message with stack trace on compiler failure
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
